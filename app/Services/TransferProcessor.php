<?php

namespace App\Services;

use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Processeur spécialisé pour les transferts - gère uniquement le traitement des transactions
 */
class TransferProcessor
{
    protected TransferRepository $transferRepository;
    protected WalletRepository $walletRepository;
    protected TransactionManager $transactionManager;
    protected BalanceManager $balanceManager;

    public function __construct(
        TransferRepository $transferRepository,
        WalletRepository $walletRepository,
        TransactionManager $transactionManager,
        BalanceManager $balanceManager
    ) {
        $this->transferRepository = $transferRepository;
        $this->walletRepository = $walletRepository;
        $this->transactionManager = $transactionManager;
        $this->balanceManager = $balanceManager;
    }

    /**
     * Traite un transfert validé entre utilisateurs
     */
    public function processTransfer(object $sender, object $receiver, object $senderWallet, ?object $receiverWallet, float $amount, array $metadata = []): array
    {
        // Générer des références uniques pour chaque transaction
        $transferRef = 'TRF_' . Str::uuid();
        $debitRef = 'DBT_' . Str::uuid();
        $creditRef = 'CRD_' . Str::uuid();

        $defaultMeta = [
            'sender_number' => $sender->telephone,
            'receiver_number' => $receiver->telephone,
            'transfer_reference' => $transferRef
        ];

        if (isset($metadata['reference_externe'])) {
            $defaultMeta['reference_externe'] = $metadata['reference_externe'];
        }

        $fullMeta = array_merge($defaultMeta, $metadata);

        // Créer automatiquement un wallet pour le destinataire s'il n'en a pas
        if (!$receiverWallet) {
            Log::info('Création automatique d\'un wallet pour le destinataire', [
                'receiver_id' => $receiver->id,
                'receiver_number' => $receiver->telephone
            ]);

            $receiverWallet = $this->walletRepository->createForUser($receiver->id, 'XOF', 0.0);

            Log::info('Wallet créé automatiquement', [
                'wallet_id' => $receiverWallet->id,
                'receiver_id' => $receiver->id
            ]);
        }

        // Exécuter le transfert dans une transaction MongoDB ACID
        $newSenderBalance = $senderWallet->balance - $amount;
        $newReceiverBalance = $receiverWallet->balance + $amount;

        $result = $this->transactionManager->executeTransferTransaction(function ($session) use (
            $senderWallet, $receiverWallet, $amount, $debitRef, $creditRef, $fullMeta,
            $newSenderBalance, $newReceiverBalance
        ) {
            // Créer la transaction de débit (expéditeur) avec session MongoDB
            $debitTransaction = $this->transferRepository->createTransferTransaction([
                'wallet_id' => $senderWallet->id,
                'type' => 'transfer',
                'amount' => -$amount, // Montant négatif pour le débit
                'status' => 'success',
                'reference' => $debitRef,
                'meta' => array_merge($fullMeta, ['transfer_type' => 'debit'])
            ], $session);

            // Créer la transaction de crédit (destinataire) avec session MongoDB
            $creditTransaction = $this->transferRepository->createTransferTransaction([
                'wallet_id' => $receiverWallet->id,
                'type' => 'transfer',
                'amount' => $amount, // Montant positif pour le crédit
                'status' => 'success',
                'reference' => $creditRef,
                'meta' => array_merge($fullMeta, ['transfer_type' => 'credit'])
            ], $session);

            // Mettre à jour les soldes (dans la transaction)
            $this->transferRepository->updateWalletBalance($senderWallet->id, $newSenderBalance, $session);
            $this->transferRepository->updateWalletBalance($receiverWallet->id, $newReceiverBalance, $session);

            return [
                'debit_transaction' => $debitTransaction,
                'credit_transaction' => $creditTransaction
            ];
        });

        return [
            'reference' => $transferRef,
            'sender' => [
                'number' => $sender->telephone,
                'name' => $sender->nom . ' ' . $sender->prenom,
                'new_balance' => $newSenderBalance
            ],
            'receiver' => [
                'number' => $receiver->telephone,
                'name' => $receiver->nom . ' ' . $receiver->prenom,
                'new_balance' => $newReceiverBalance
            ],
            'amount' => $amount,
            'debit_transaction' => $result['debit_transaction'],
            'credit_transaction' => $result['credit_transaction']
        ];
    }

    /**
     * Définit un verrou anti-dédoublement pour le transfert
     */
    public function setProcessingLock(string $senderNumber, string $receiverNumber, float $amount): string
    {
        $cacheKey = "transfer_processing_{$senderNumber}_{$receiverNumber}_{$amount}";
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
     * Vérifie si un transfert est en cours de traitement
     */
    public function isProcessing(string $senderNumber, string $receiverNumber, float $amount): bool
    {
        $cacheKey = "transfer_processing_{$senderNumber}_{$receiverNumber}_{$amount}";
        return Cache::has($cacheKey);
    }

    /**
     * Calcule les nouveaux soldes après transfert
     */
    public function calculateNewBalances(object $senderWallet, object $receiverWallet, float $amount): array
    {
        return [
            'sender_new_balance' => $senderWallet->balance - $amount,
            'receiver_new_balance' => $receiverWallet->balance + $amount
        ];
    }

    /**
     * Valide que les soldes sont cohérents après traitement
     */
    public function validateBalancesAfterProcessing(string $senderWalletId, string $receiverWalletId, float $expectedSenderBalance, float $expectedReceiverBalance): bool
    {
        $actualSenderBalance = $this->balanceManager->getBalance($senderWalletId);
        $actualReceiverBalance = $this->balanceManager->getBalance($receiverWalletId);

        return abs($actualSenderBalance - $expectedSenderBalance) < 0.01 &&
               abs($actualReceiverBalance - $expectedReceiverBalance) < 0.01;
    }

    /**
     * Crée un wallet pour un utilisateur s'il n'en a pas
     */
    public function ensureUserHasWallet(string $userId, string $currency = 'XOF', float $initialBalance = 0.0): object
    {
        $existingWallet = $this->transferRepository->getUserWallet($userId);

        if ($existingWallet) {
            return $existingWallet;
        }

        Log::info('Création automatique d\'un wallet pour l\'utilisateur', [
            'user_id' => $userId
        ]);

        $wallet = $this->walletRepository->createForUser($userId, $currency, $initialBalance);

        Log::info('Wallet créé automatiquement', [
            'wallet_id' => $wallet->id,
            'user_id' => $userId
        ]);

        return $wallet;
    }
}
