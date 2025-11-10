<?php

namespace App\Repositories;

use App\Models\Wallet;
use Illuminate\Support\Str;

class WalletRepository
{
    /**
     * Crée un nouveau wallet pour un utilisateur
     */
    public function createForUser(string $userId, string $currency = 'XOF', float $initialBalance = 0.0): Wallet
    {
        return Wallet::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'balance' => $initialBalance,
            'currency' => $currency
        ]);
    }

    /**
     * Trouve le wallet d'un utilisateur
     */
    public function findByUserId(string $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    /**
     * Met à jour le solde d'un wallet
     */
    public function updateBalance(string $walletId, float $newBalance): bool
    {
        return Wallet::where('id', $walletId)->update(['balance' => $newBalance]);
    }

    /**
     * Vérifie si un utilisateur a déjà un wallet
     */
    public function userHasWallet(string $userId): bool
    {
        return Wallet::where('user_id', $userId)->exists();
    }
}
