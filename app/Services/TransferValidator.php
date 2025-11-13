<?php

namespace App\Services;

use App\Repositories\TransferRepository;
use App\Rules\SenegalPhone;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Validateur spécialisé pour les transferts avec règles métier strictes
 */
class TransferValidator
{
    protected TransferRepository $transferRepository;

    // Limites de transfert
    const MIN_TRANSFER_AMOUNT = 500; // 500 FCFA minimum
    const MAX_TRANSFER_AMOUNT = 1000000; // 1 million FCFA maximum
    const MAX_DAILY_TRANSFERS = 5; // 5 transferts par jour
    const MAX_MONTHLY_AMOUNT = 5000000; // 5 millions FCFA par mois

    public function __construct(TransferRepository $transferRepository)
    {
        $this->transferRepository = $transferRepository;
    }

    /**
     * Valide complètement une demande de transfert
     */
    public function validateTransferRequest(string $senderNumber, string $receiverNumber, float $amount, array $metadata = []): array
    {
        $errors = [];

        // Validation du montant
        $amountErrors = $this->validateAmount($amount);
        $errors = array_merge($errors, $amountErrors);

        // Validation des utilisateurs
        $userErrors = $this->validateUsers($senderNumber, $receiverNumber);
        $errors = array_merge($errors, $userErrors);

        // Validation des wallets
        if (empty($errors)) {
            $walletErrors = $this->validateWallets($senderNumber, $receiverNumber);
            $errors = array_merge($errors, $walletErrors);
        }

        // Validation des limites expéditeur
        if (empty($errors)) {
            $sender = $this->transferRepository->findUserByNumber($senderNumber);
            if ($sender) {
                $limitErrors = $this->validateSenderLimits($sender->id, $amount);
                $errors = array_merge($errors, $limitErrors);
            }
        }

        // Validation de la référence externe
        $referenceErrors = $this->validateExternalReference($metadata['reference_externe'] ?? null, $senderNumber);
        $errors = array_merge($errors, $referenceErrors);

        // Validation anti-dédoublement
        if (empty($errors)) {
            $duplicateErrors = $this->validateNoDuplicateProcessing($senderNumber, $receiverNumber, $amount);
            $errors = array_merge($errors, $duplicateErrors);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valide le montant du transfert
     */
    public function validateAmount(float $amount): array
    {
        $errors = [];

        if ($amount <= 0) {
            $errors[] = 'Le montant doit être supérieur à 0';
        }

        if ($amount < self::MIN_TRANSFER_AMOUNT) {
            $errors[] = sprintf('Le montant minimum de transfert est de %d FCFA', self::MIN_TRANSFER_AMOUNT);
        }

        if ($amount > self::MAX_TRANSFER_AMOUNT) {
            $errors[] = sprintf('Le montant maximum de transfert est de %d FCFA', self::MAX_TRANSFER_AMOUNT);
        }

        // Vérification que le montant est un multiple de 100 (pas de centimes)
        if ($amount != round($amount, 0)) {
            $errors[] = 'Le montant doit être un nombre entier (pas de centimes)';
        }

        return $errors;
    }

    /**
     * Valide les utilisateurs expéditeur et destinataire
     */
    public function validateUsers(string $senderNumber, string $receiverNumber): array
    {
        $errors = [];

        if (empty($senderNumber) || empty($receiverNumber)) {
            $errors[] = 'Les numéros expéditeur et destinataire sont requis';
            return $errors;
        }

        if ($senderNumber === $receiverNumber) {
            $errors[] = 'Impossible de faire un transfert vers soi-même';
            return $errors;
        }

        // Validation du format des numéros
        $phoneErrors = $this->validatePhoneNumbers($senderNumber, $receiverNumber);
        $errors = array_merge($errors, $phoneErrors);

        // Vérification existence utilisateurs
        $sender = $this->transferRepository->findUserByNumber($senderNumber);
        if (!$sender) {
            $errors[] = 'Utilisateur expéditeur non trouvé';
        }

        $receiver = $this->transferRepository->findUserByNumber($receiverNumber);
        if (!$receiver) {
            $errors[] = 'Utilisateur destinataire non trouvé';
        }

        // Vérification statuts utilisateurs
        if ($sender && isset($sender->status) && $sender->status !== 'active') {
            $errors[] = 'Compte expéditeur non actif';
        }

        if ($receiver && isset($receiver->status) && $receiver->status !== 'active') {
            $errors[] = 'Compte destinataire non actif';
        }

        return $errors;
    }

    /**
     * Valide le format des numéros de téléphone
     */
    public function validatePhoneNumbers(string $senderNumber, string $receiverNumber): array
    {
        $errors = [];

        $senegalPhoneRule = new SenegalPhone();

        if (!$senegalPhoneRule->passes('telephone', $senderNumber)) {
            $errors[] = 'Format du numéro expéditeur invalide';
        }

        if (!$senegalPhoneRule->passes('telephone', $receiverNumber)) {
            $errors[] = 'Format du numéro destinataire invalide';
        }

        return $errors;
    }

    /**
     * Valide les wallets des utilisateurs
     */
    public function validateWallets(string $senderNumber, string $receiverNumber): array
    {
        $errors = [];

        $sender = $this->transferRepository->findUserByNumber($senderNumber);
        $receiver = $this->transferRepository->findUserByNumber($receiverNumber);

        if (!$sender || !$receiver) {
            return $errors; // Erreurs déjà gérées dans validateUsers
        }

        $senderWallet = $this->transferRepository->getUserWallet($sender->id);
        if (!$senderWallet) {
            $errors[] = 'Wallet expéditeur non trouvé';
        }

        // Le destinataire peut ne pas avoir de wallet, il sera créé automatiquement
        // Mais s'il en a un, on le valide
        $receiverWallet = $this->transferRepository->getUserWallet($receiver->id);
        if ($receiverWallet && $receiverWallet->balance < 0) {
            $errors[] = 'Wallet destinataire en solde négatif';
        }

        return $errors;
    }

    /**
     * Valide les limites de l'expéditeur
     */
    public function validateSenderLimits(string $senderId, float $amount): array
    {
        $errors = [];

        // Limite de base sur le montant maximum par transfert
        if ($amount > self::MAX_TRANSFER_AMOUNT) {
            $errors[] = sprintf('Montant maximum par transfert : %d FCFA', self::MAX_TRANSFER_AMOUNT);
        }

        // TODO: Implémenter les vérifications de quotas journaliers/mensuels
        // quand les méthodes appropriées seront ajoutées au TransferRepository

        return $errors;
    }

    /**
     * Valide la référence externe pour éviter les doublons
     */
    public function validateExternalReference(?string $externalRef, string $senderNumber): array
    {
        $errors = [];

        if ($externalRef) {
            // Vérifier le format de la référence
            if (!preg_match('/^[A-Za-z0-9\-_]{1,50}$/', $externalRef)) {
                $errors[] = 'Format de référence externe invalide (lettres, chiffres, tirets et underscores uniquement, max 50 caractères)';
            }

            // Vérifier l'unicité
            $existingTransaction = $this->transferRepository->findTransactionByExternalReference($externalRef, $senderNumber);
            if ($existingTransaction) {
                $errors[] = 'Cette référence externe a déjà été utilisée pour un transfert';
            }
        }

        return $errors;
    }

    /**
     * Valide qu'il n'y a pas de traitement en cours (anti-dédoublement)
     */
    public function validateNoDuplicateProcessing(string $senderNumber, string $receiverNumber, float $amount): array
    {
        $errors = [];

        $cacheKey = "transfer_processing_{$senderNumber}_{$receiverNumber}_{$amount}";
        if (Cache::has($cacheKey)) {
            $errors[] = 'Un transfert similaire est déjà en cours de traitement';
        }

        return $errors;
    }

    /**
     * Valide le solde disponible pour le transfert
     */
    public function validateSufficientBalance(string $senderWalletId, float $amount): array
    {
        $errors = [];

        $balanceManager = app(BalanceManager::class);
        if (!$balanceManager->hasSufficientBalance($senderWalletId, $amount)) {
            $errors[] = 'Solde insuffisant pour effectuer ce transfert';
        }

        return $errors;
    }

    /**
     * Valide que le destinataire peut recevoir le transfert
     */
    public function validateReceiverCanReceive(string $receiverWalletId, float $amount, float $maxBalance = 10000000): array
    {
        $errors = [];

        $balanceManager = app(BalanceManager::class);
        if (!$balanceManager->canReceiveAmount($receiverWalletId, $amount, $maxBalance)) {
            $errors[] = 'Le destinataire ne peut pas recevoir ce transfert (limite dépassée)';
        }

        return $errors;
    }
}
