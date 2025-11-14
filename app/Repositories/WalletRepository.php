<?php

namespace App\Repositories;

use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
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
     * Trouve un wallet par son ID
     */
    public function find(string $walletId): ?Wallet
    {
        return Wallet::find($walletId);
    }

    /**
     * Trouve le wallet d'un utilisateur
     */
    public function findByUserId(string $userId): ?Wallet
    {
        return Cache::remember("wallet_user_{$userId}", 1800, function () use ($userId) {
            return Wallet::where('user_id', $userId)->first();
        });
    }

    /**
     * Met à jour le solde d'un wallet
     *
     * @param string $walletId
     * @param float $newBalance
     * @param mixed $session Session MongoDB optionnelle pour les transactions
     * @return bool
     */
    public function updateBalance(string $walletId, float $newBalance, $session = null): bool
    {
        $query = Wallet::where('id', $walletId);

        if ($session) {
            // Utiliser la session MongoDB pour les transactions
            return $query->update(['balance' => $newBalance], ['session' => $session]);
        }

        return $query->update(['balance' => $newBalance]);
    }

    /**
     * Vérifie si un utilisateur a déjà un wallet
     */
    public function userHasWallet(string $userId): bool
    {
        return Wallet::where('user_id', $userId)->exists();
    }
}
