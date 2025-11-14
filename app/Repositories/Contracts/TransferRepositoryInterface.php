<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

interface TransferRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Trouver un utilisateur par son numéro de téléphone
     */
    public function findUserByNumber(string $numero): ?User;

    /**
     * Obtenir le wallet d'un utilisateur
     */
    public function getUserWallet(string $userId): ?Wallet;

    /**
     * Créer une transaction de transfert
     */
    public function createTransferTransaction(array $data, $session = null): Transaction;

    /**
     * Mettre à jour le solde d'un wallet
     */
    public function updateWalletBalance(string $walletId, float $newBalance, $session = null): bool;

    /**
     * Obtenir les transactions par référence (pour les transferts liés)
     */
    public function getTransactionsByReference(string $reference): array;

    /**
     * Obtenir l'historique des transferts d'un utilisateur
     */
    public function getUserTransferHistory(string $userId, int $page = 1, int $limit = 10);

    /**
     * Trouver une transaction par référence externe pour un numéro d'expéditeur
     */
    public function findTransactionByExternalReference(string $externalRef, string $senderNumber): ?Transaction;
}
