<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TransactionRepositoryInterface
{
    /**
     * Récupère les transactions d'un utilisateur par type
     */
    public function getUserTransactionsByType(string $telephone, string $type, int $page = 1, int $limit = 10): Collection;

    /**
     * Récupère toutes les transactions d'un utilisateur
     */
    public function getUserTransactions(string $telephone, int $page = 1, int $limit = 10): Collection;

    /**
     * Compte le nombre total de transactions d'un utilisateur
     */
    public function countUserTransactions(string $telephone, string $type = null): int;

    /**
     * Récupère les transactions par période
     */
    public function getUserTransactionsByPeriod(
        string $telephone,
        \DateTime $startDate,
        \DateTime $endDate,
        string $type = null
    ): Collection;

    /**
     * Récupère les transactions avec des relations
     */
    public function getUserTransactionsWithRelations(string $telephone, int $page = 1, int $limit = 10): Collection;
}
