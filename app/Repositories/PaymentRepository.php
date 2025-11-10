<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Wallet;

class PaymentRepository extends BaseRepository
{
    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    /**
     * Trouver un utilisateur par son numéro de téléphone
     */
    public function findUserByPhone(string $telephone): ?User
    {
        return User::where('telephone', $telephone)
            ->orWhere('telephone', '+221' . $telephone)
            ->orWhere('telephone', str_replace('+221', '', $telephone))
            ->first();
    }

    /**
     * Trouver un marchand actif par son code
     */
    public function findMerchantByCode(string $code): ?Merchant
    {
        return Merchant::where('code', $code)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Obtenir le wallet d’un utilisateur
     */
    public function getUserWallet(string $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    /**
     * Obtenir le wallet d’un marchand
     */
    public function getMerchantWallet(string $merchantId): ?Wallet
    {
        return Wallet::where('merchant_id', $merchantId)->first();
    }

    /**
     * Créer une transaction de paiement
     */
    public function createPaymentTransaction(array $data): Transaction
    {
        return $this->create([
            'wallet_id' => $data['wallet_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => $data['status'] ?? 'pending',
            'reference' => $data['reference'],
            'meta' => $data['meta'] ?? []
        ]);
    }

    /**
     * Mettre à jour le solde d’un wallet
     */
    public function updateWalletBalance(string $walletId, float $newBalance): bool
    {
        return Wallet::where('id', $walletId)
            ->update(['balance' => $newBalance]);
    }

    /**
     * Obtenir l’historique des paiements d’un utilisateur
     */
    public function getUserPaymentHistory(string $userId, int $page = 1, int $limit = 10)
    {
        $wallet = $this->getUserWallet($userId);
        if (!$wallet) return collect([]);

        return $this->all([
            'wallet_id' => $wallet->id,
            'type' => ['payment']
        ], $page, $limit);
    }

    /**
     * Obtenir l’historique des paiements reçus par un marchand
     */
    public function getMerchantPaymentHistory(string $merchantId, int $page = 1, int $limit = 10)
    {
        $wallet = $this->getMerchantWallet($merchantId);
        if (!$wallet) return collect([]);

        return $this->all([
            'wallet_id' => $wallet->id,
            'type' => ['payment']
        ], $page, $limit);
    }
}
