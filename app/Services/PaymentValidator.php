<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Validateur spécialisé pour les paiements avec règles métier strictes
 */
class PaymentValidator
{
    protected PaymentRepository $paymentRepository;

    // Limites de paiement
    const MIN_PAYMENT_AMOUNT = 100; // 100 FCFA minimum
    const MAX_PAYMENT_AMOUNT = 500000; // 500,000 FCFA maximum
    const MAX_DAILY_PAYMENTS = 10; // 10 paiements par jour
    const MAX_MONTHLY_AMOUNT = 2000000; // 2 millions FCFA par mois

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Valide complètement une demande de paiement
     */
    public function validatePaymentRequest(object $user, string $merchantCode, float $amount, array $metadata = []): array
    {
        $errors = [];

        // Validation du montant
        $amountErrors = $this->validateAmount($amount);
        $errors = array_merge($errors, $amountErrors);

        // Validation du marchand
        $merchantErrors = $this->validateMerchant($merchantCode);
        $errors = array_merge($errors, $amountErrors);

        // Validation de l'utilisateur
        $userErrors = $this->validateUser($user);
        $errors = array_merge($errors, $userErrors);

        // Validation des limites utilisateur
        if (empty($errors)) {
            $limitErrors = $this->validateUserLimits($user->id, $amount);
            $errors = array_merge($errors, $limitErrors);
        }

        // Validation de la référence externe
        $referenceErrors = $this->validateExternalReference($metadata['reference_externe'] ?? null, $user->id);
        $errors = array_merge($errors, $referenceErrors);

        // Validation anti-dédoublement
        if (empty($errors)) {
            $duplicateErrors = $this->validateNoDuplicateProcessing($user->id, $merchantCode, $amount);
            $errors = array_merge($errors, $duplicateErrors);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valide le montant du paiement
     */
    public function validateAmount(float $amount): array
    {
        $errors = [];

        if ($amount <= 0) {
            $errors[] = 'Le montant doit être supérieur à 0';
        }

        if ($amount < self::MIN_PAYMENT_AMOUNT) {
            $errors[] = sprintf('Le montant minimum de paiement est de %d FCFA', self::MIN_PAYMENT_AMOUNT);
        }

        if ($amount > self::MAX_PAYMENT_AMOUNT) {
            $errors[] = sprintf('Le montant maximum de paiement est de %d FCFA', self::MAX_PAYMENT_AMOUNT);
        }

        // Vérification que le montant est un multiple de 100 (pas de centimes)
        if ($amount != round($amount, 0)) {
            $errors[] = 'Le montant doit être un nombre entier (pas de centimes)';
        }

        return $errors;
    }

    /**
     * Valide le marchand
     */
    public function validateMerchant(string $merchantCode): array
    {
        $errors = [];

        if (empty($merchantCode)) {
            $errors[] = 'Le code marchand est requis';
            return $errors;
        }

        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);

        if (!$merchant) {
            $errors[] = 'Code marchand invalide ou marchand inexistant';
            return $errors;
        }

        if ($merchant->status !== 'active') {
            $errors[] = 'Le marchand n\'est pas actif';
        }

        // Vérifier que le marchand a un wallet valide
        $merchantWallet = $this->paymentRepository->getMerchantWallet($merchant->id);
        if (!$merchantWallet) {
            $errors[] = 'Configuration wallet marchand invalide';
        }

        return $errors;
    }

    /**
     * Valide l'utilisateur
     */
    public function validateUser(object $user): array
    {
        $errors = [];

        if (!$user) {
            $errors[] = 'Utilisateur non trouvé';
            return $errors;
        }

        if (empty($user->telephone)) {
            $errors[] = 'Numéro de téléphone utilisateur manquant';
        }

        if (!$user->wallet) {
            $errors[] = 'Wallet utilisateur non trouvé';
        }

        // Vérifier le statut de l'utilisateur si applicable
        if (isset($user->status) && $user->status !== 'active') {
            $errors[] = 'Compte utilisateur non actif';
        }

        return $errors;
    }

    /**
     * Valide les limites utilisateur (quotas journaliers/mensuels)
     * Note: Pour l'instant, seules les limites de base sont vérifiées.
     * Les vérifications de quotas journaliers/mensuels peuvent être ajoutées plus tard.
     */
    public function validateUserLimits(string $userId, float $amount): array
    {
        $errors = [];

        // Limite de base sur le montant maximum par paiement
        if ($amount > self::MAX_PAYMENT_AMOUNT) {
            $errors[] = sprintf('Montant maximum par paiement : %d FCFA', self::MAX_PAYMENT_AMOUNT);
        }

        // TODO: Implémenter les vérifications de quotas journaliers/mensuels
        // quand les méthodes countUserPaymentsToday et sumUserPaymentsThisMonth
        // seront ajoutées au PaymentRepository

        return $errors;
    }

    /**
     * Valide la référence externe pour éviter les doublons
     */
    public function validateExternalReference(?string $externalRef, string $userId): array
    {
        $errors = [];

        if ($externalRef) {
            // Vérifier le format de la référence
            if (!preg_match('/^[A-Za-z0-9\-_]{1,50}$/', $externalRef)) {
                $errors[] = 'Format de référence externe invalide (lettres, chiffres, tirets et underscores uniquement, max 50 caractères)';
            }

            // Vérifier l'unicité
            $existingTransaction = $this->paymentRepository->findTransactionByExternalReference($externalRef, $userId);
            if ($existingTransaction) {
                $errors[] = 'Cette référence externe a déjà été utilisée pour un paiement';
            }
        }

        return $errors;
    }

    /**
     * Valide qu'il n'y a pas de traitement en cours (anti-dédoublement)
     */
    public function validateNoDuplicateProcessing(string $userId, string $merchantCode, float $amount): array
    {
        $errors = [];

        $cacheKey = "payment_processing_{$userId}_{$merchantCode}_{$amount}";
        if (Cache::has($cacheKey)) {
            $errors[] = 'Un paiement similaire est déjà en cours de traitement';
        }

        return $errors;
    }

    /**
     * Valide le solde disponible pour le paiement
     */
    public function validateSufficientBalance(string $userWalletId, float $amount): array
    {
        $errors = [];

        $balanceManager = app(BalanceManager::class);
        if (!$balanceManager->hasSufficientBalance($userWalletId, $amount)) {
            $errors[] = 'Solde insuffisant pour effectuer ce paiement';
        }

        return $errors;
    }

    /**
     * Valide que le marchand peut recevoir le paiement
     */
    public function validateMerchantCanReceive(string $merchantWalletId, float $amount, float $maxBalance = 2000000): array
    {
        $errors = [];

        $balanceManager = app(BalanceManager::class);
        if (!$balanceManager->canReceiveAmount($merchantWalletId, $amount, $maxBalance)) {
            $errors[] = 'Le marchand ne peut pas recevoir ce paiement (limite dépassée)';
        }

        return $errors;
    }
}
