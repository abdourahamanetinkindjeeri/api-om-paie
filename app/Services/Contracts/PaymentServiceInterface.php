<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentServiceInterface
{
    /**
     * Effectuer un paiement vers un marchand
     */
    public function payMerchant(object $user, string $merchantCode, float $amount, array $metadata = []): array;

    /**
     * Vérifier si un marchand existe
     */
    public function checkMerchantExists(string $merchantCode): bool;

    /**
     * Obtenir les informations d’un marchand
     */
    public function getMerchantInfo(string $merchantCode): ?array;

    /**
     * Obtenir l’historique des paiements d’un utilisateur
     */
    public function getUserPaymentHistory(string $userPhone, int $page = 1, int $limit = 10): LengthAwarePaginator;

    /**
     * Obtenir l’historique des paiements reçus par un marchand
     */
    public function getMerchantPaymentHistory(string $merchantCode, int $page = 1, int $limit = 10): LengthAwarePaginator;
}
