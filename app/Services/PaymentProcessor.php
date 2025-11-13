<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Processeur spécialisé pour les paiements - gère uniquement le traitement des transactions
 */
class PaymentProcessor
{
    protected PaymentRepository $paymentRepository;
    protected TransactionManager $transactionManager;
    protected BalanceManager $balanceManager;

    public function __construct(
        PaymentRepository $paymentRepository,
        TransactionManager $transactionManager,
        BalanceManager $balanceManager
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->transactionManager = $transactionManager;
        $this->balanceManager = $balanceManager;
    }

    /**
     * Traite un paiement validé vers un marchand
     */
    public function processPayment(object $user, object $merchant, object $userWallet, object $merchantWallet, float $amount, array $metadata = []): array
    {
        // Générer références uniques
        $paymentRef = 'PAY_' . Str::uuid();
        $debitRef = 'DEB_' . Str::uuid();
        $creditRef = 'CRD_' . Str::uuid();

        $defaultMeta = [
            'user_phone' => $user->telephone,
            'merchant_code' => $merchant->code,
            'merchant_name' => $merchant->name,
            'payment_reference' => $paymentRef
        ];

        if (isset($metadata['reference_externe'])) {
            $defaultMeta['reference_externe'] = $metadata['reference_externe'];
        }

        $fullMeta = array_merge($defaultMeta, $metadata);
        $newUserBalance = $userWallet->balance - $amount;
        $newMerchantBalance = $merchantWallet->balance + $amount;

        // Exécuter le paiement dans une transaction MongoDB ACID
        $result = $this->transactionManager->executePaymentTransaction(function ($session) use (
            $userWallet, $merchantWallet, $amount, $debitRef, $creditRef, $fullMeta
        ) {
            // Créer transaction débit avec session MongoDB
            $debitTransaction = $this->paymentRepository->createPaymentTransaction([
                'wallet_id' => $userWallet->id,
                'type' => 'payment',
                'amount' => -$amount,
                'status' => 'success',
                'reference' => $debitRef,
                'meta' => array_merge($fullMeta, ['transaction_type' => 'debit', 'direction' => 'out'])
            ], $session);

            // Créer transaction crédit avec session MongoDB
            $creditTransaction = $this->paymentRepository->createPaymentTransaction([
                'wallet_id' => $merchantWallet->id,
                'type' => 'payment',
                'amount' => $amount,
                'status' => 'success',
                'reference' => $creditRef,
                'meta' => array_merge($fullMeta, ['transaction_type' => 'credit', 'direction' => 'in'])
            ], $session);

            // Mise à jour balances avec BalanceManager (dans la transaction)
            $this->balanceManager->debit($userWallet->id, $amount, $session);
            $this->balanceManager->credit($merchantWallet->id, $amount, $session);

            return [
                'debit_transaction' => $debitTransaction,
                'credit_transaction' => $creditTransaction
            ];
        });

        return [
            'reference' => $paymentRef,
            'user' => [
                'phone' => $user->telephone,
                'name' => $user->nom . ' ' . $user->prenom,
                'new_balance' => $this->balanceManager->getBalance($userWallet->id)
            ],
            'merchant' => [
                'code' => $merchant->code,
                'name' => $merchant->name,
                'new_balance' => $this->balanceManager->getBalance($merchantWallet->id)
            ],
            'amount' => $amount,
            'debit_transaction' => $result['debit_transaction'],
            'credit_transaction' => $result['credit_transaction'],
            'metadata' => $fullMeta
        ];
    }

    /**
     * Définit un verrou anti-dédoublement pour le paiement
     */
    public function setProcessingLock(object $user, string $merchantCode, float $amount): string
    {
        $cacheKey = "payment_processing_{$user->id}_{$merchantCode}_{$amount}";
        Cache::put($cacheKey, true, 30); // 30 secondes de protection
        return $cacheKey;
    }

    /**
     * Libère le verrou anti-dédoublement
     */
    public function releaseProcessingLock(string $cacheKey): void
    {
        Cache::forget($cacheKey);
    }

    /**
     * Vérifie si un paiement est en cours de traitement
     */
    public function isProcessing(object $user, string $merchantCode, float $amount): bool
    {
        $cacheKey = "payment_processing_{$user->id}_{$merchantCode}_{$amount}";
        return Cache::has($cacheKey);
    }

    /**
     * Calcule les nouveaux soldes après paiement
     */
    public function calculateNewBalances(object $userWallet, object $merchantWallet, float $amount): array
    {
        return [
            'user_new_balance' => $userWallet->balance - $amount,
            'merchant_new_balance' => $merchantWallet->balance + $amount
        ];
    }

    /**
     * Valide que les soldes sont cohérents après traitement
     */
    public function validateBalancesAfterProcessing(string $userWalletId, string $merchantWalletId, float $expectedUserBalance, float $expectedMerchantBalance): bool
    {
        $actualUserBalance = $this->balanceManager->getBalance($userWalletId);
        $actualMerchantBalance = $this->balanceManager->getBalance($merchantWalletId);

        return abs($actualUserBalance - $expectedUserBalance) < 0.01 &&
               abs($actualMerchantBalance - $expectedMerchantBalance) < 0.01;
    }
}
