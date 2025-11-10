<?php

namespace App\Services\Contracts;

interface HistoryServiceInterface
{
    /**
     * Récupère l'historique combiné des paiements et transferts d'un utilisateur
     */
    public function getUserHistory(string $telephone, int $page = 1, int $limit = 10): array;

    /**
     * Récupère l'historique des transferts d'un utilisateur
     */
    public function getUserTransferHistory(string $telephone, int $page = 1, int $limit = 10): array;

    /**
     * Récupère l'historique des paiements d'un utilisateur
     */
    public function getUserPaymentHistory(string $telephone, int $page = 1, int $limit = 10): array;
}
