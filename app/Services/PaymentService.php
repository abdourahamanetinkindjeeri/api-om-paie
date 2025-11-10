<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Services\Contracts\PaymentServiceInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService extends BaseService implements PaymentServiceInterface
{
    protected PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $repository)
    {
        parent::__construct($repository);
        $this->paymentRepository = $repository;
    }

    /**
     * Effectuer un paiement vers un marchand
     */
    public function payMerchant($user, string $merchantCode, float $amount, array $metadata = []): array
    {
        // Remplacer DB::transaction par une gestion manuelle pour MongoDB standalone
        try {
            // Validation du montant
            if ($amount <= 0) {
                throw new Exception('Le montant doit être supérieur à 0');
            }

            // Trouver le marchand
            $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
            if (!$merchant) {
                throw new Exception('Code marchand invalide ou marchand inactif');
            }

            // Obtenir les wallets
            $userWallet = $user->wallet;
            $merchantWallet = $this->paymentRepository->getMerchantWallet($merchant->id);

            if (!$userWallet) {
                throw new Exception('Wallet de l\'utilisateur non trouvé');
            }

            if (!$merchantWallet) {
                throw new Exception('Wallet du marchand non trouvé');
            }

            // Vérifier le solde suffisant
            if ($userWallet->balance < $amount) {
                throw new Exception('Solde insuffisant');
            }

            // Générer des références uniques
            $paymentRef = 'PAY_' . Str::uuid();
            $debitRef = 'DEB_' . Str::uuid();
            $creditRef = 'CRD_' . Str::uuid();

            // Métadonnées par défaut
            $defaultMeta = [
                'user_phone' => $user->telephone,
                'merchant_code' => $merchantCode,
                'merchant_name' => $merchant->name,
                'payment_reference' => $paymentRef
            ];

            $fullMeta = array_merge($defaultMeta, $metadata);

            // Créer la transaction de débit (utilisateur)
            $debitTransaction = $this->paymentRepository->createPaymentTransaction([
                'wallet_id' => $userWallet->id,
                'type' => 'payment',
                'amount' => -$amount, // Montant négatif pour le débit
                'status' => 'success',
                'reference' => $debitRef,
                'meta' => array_merge($fullMeta, [
                    'transaction_type' => 'debit',
                    'direction' => 'out'
                ])
            ]);

            // Créer la transaction de crédit (marchand)
            $creditTransaction = $this->paymentRepository->createPaymentTransaction([
                'wallet_id' => $merchantWallet->id,
                'type' => 'payment',
                'amount' => $amount, // Montant positif pour le crédit
                'status' => 'success',
                'reference' => $creditRef,
                'meta' => array_merge($fullMeta, [
                    'transaction_type' => 'credit',
                    'direction' => 'in'
                ])
            ]);

            // Mettre à jour les soldes
            $newUserBalance = $userWallet->balance - $amount;
            $newMerchantBalance = $merchantWallet->balance + $amount;

            $this->paymentRepository->updateWalletBalance($userWallet->id, $newUserBalance);
            $this->paymentRepository->updateWalletBalance($merchantWallet->id, $newMerchantBalance);

            return [
                'success' => true,
                'reference' => $paymentRef,
                'user' => [
                    'phone' => $user->telephone,
                    'name' => $user->nom . ' ' . $user->prenom,
                    'new_balance' => $newUserBalance
                ],
                'merchant' => [
                    'code' => $merchantCode,
                    'name' => $merchant->name,
                    'new_balance' => $newMerchantBalance
                ],
                'amount' => $amount,
                'debit_transaction' => $debitTransaction,
                'credit_transaction' => $creditTransaction,
                'metadata' => $metadata
            ];
        } catch (Exception $e) {
            // En cas d'erreur, relancer l'exception
            throw $e;
        }
    }

    /**
     * Vérifier si un code marchand existe
     */
    public function checkMerchantExists(string $merchantCode): bool
    {
        return $this->paymentRepository->findMerchantByCode($merchantCode) !== null;
    }

    /**
     * Obtenir les informations d'un marchand
     */
    public function getMerchantInfo(string $merchantCode): ?array
    {
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);

        if (!$merchant) {
            return null;
        }

        $wallet = $this->paymentRepository->getMerchantWallet($merchant->id);

        return [
            'code' => $merchant->code,
            'name' => $merchant->name,
            'telephone' => $merchant->telephone,
            'email' => $merchant->email,
            'status' => $merchant->status,
            'balance' => $wallet ? $wallet->balance : 0
        ];
    }

    /**
     * Obtenir l'historique des paiements d'un utilisateur
     */
    public function getUserPaymentHistory(string $userPhone, int $page = 1, int $limit = 10): array
    {
        $user = $this->paymentRepository->findUserByPhone($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        $result = $this->paymentRepository->getUserPaymentHistory($user->id, $page, $limit);
        return $result->toArray();
    }

    /**
     * Obtenir l'historique des paiements reçus par un marchand
     */
    public function getMerchantPaymentHistory(string $merchantCode, int $page = 1, int $limit = 10): array
    {
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
        if (!$merchant) {
            throw new Exception('Marchand non trouvé');
        }

        $result = $this->paymentRepository->getMerchantPaymentHistory($merchant->id, $page, $limit);
        return $result->items();
    }
}
