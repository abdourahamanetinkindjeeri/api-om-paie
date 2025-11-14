<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;

interface WalletRepositoryInterface
{
    /**
     * Crée un nouveau wallet pour un utilisateur
     */
    public function createForUser(string $userId, string $currency = 'XOF', float $initialBalance = 0.0): Wallet;

    /**
     * Trouve un wallet par son ID
     */
    public function find(string $walletId): ?Wallet;

    /**
     * Trouve le wallet d'un utilisateur
     */
    public function findByUserId(string $userId): ?Wallet;

    /**
     * Met à jour le solde d'un wallet
     */
    public function updateBalance(string $walletId, float $newBalance, $session = null): bool;

    /**
     * Vérifie si un utilisateur a déjà un wallet
     */
    public function userHasWallet(string $userId): bool;
}
