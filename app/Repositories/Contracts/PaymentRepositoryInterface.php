<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Wallet;

interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Trouver un utilisateur par son numéro de téléphone
     */
    public function findUserByPhone(string $telephone): ?User;

    /**
     * Trouver un marchand actif par son code
     */
    public function findMerchantByCode(string $code): ?Merchant;

    /**
     * Obtenir le wallet d'un utilisateur
     */
    public function getUserWallet(string $userId): ?Wallet;

    /**
     * Obtenir le wallet d'un marchand
     */
    public function getMerchantWallet(string $merchantId): ?Wallet;

    /**
     * Créer une transaction de paiement
     */
    public function createPaymentTransaction(array $data, $session = null): Transaction;

    /**
     * Mettre à jour le solde d'un wallet
     */
    public function updateWalletBalance(string $walletId, float $newBalance): bool;

    /**
     * Obtenir l'historique des paiements d'un utilisateur
     */
    public function getUserPaymentHistory(string $userId, int $page = 1, int $limit = 10);

    /**
     * Obtenir l'historique des paiements reçus par un marchand
     */
    public function getMerchantPaymentHistory(string $merchantId, int $page = 1, int $limit = 10);

    /**
     * Trouver une transaction par référence externe pour un utilisateur
     */
    public function findTransactionByExternalReference(string $externalRef, string $userId): ?Transaction;
}
