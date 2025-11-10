<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class TransactionRepository
{
    /**
     * Récupère les transactions d'un utilisateur par type
     */
    public function getUserTransactionsByType(string $telephone, string $type, int $page = 1, int $limit = 10): Collection
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !$user->wallet) {
            return collect([]);
        }

        $offset = ($page - 1) * $limit;

        return Transaction::where('wallet_id', $user->wallet->id)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Récupère toutes les transactions d'un utilisateur
     */
    public function getUserTransactions(string $telephone, int $page = 1, int $limit = 10): Collection
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !$user->wallet) {
            return collect([]);
        }

        $offset = ($page - 1) * $limit;

        return Transaction::where('wallet_id', $user->wallet->id)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Compte le nombre total de transactions d'un utilisateur
     */
    public function countUserTransactions(string $telephone, string $type = null): int
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !$user->wallet) {
            return 0;
        }

        $query = Transaction::where('wallet_id', $user->wallet->id);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->count();
    }

    /**
     * Récupère les transactions par période
     */
    public function getUserTransactionsByPeriod(
        string $telephone,
        \DateTime $startDate,
        \DateTime $endDate,
        string $type = null
    ): Collection {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !$user->wallet) {
            return collect([]);
        }

        $query = Transaction::where('wallet_id', $user->wallet->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Récupère les transactions avec des relations
     */
    public function getUserTransactionsWithRelations(string $telephone, int $page = 1, int $limit = 10): Collection
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !$user->wallet) {
            return collect([]);
        }

        $offset = ($page - 1) * $limit;

        return Transaction::where('wallet_id', $user->wallet->id)
            ->with(['wallet.user', 'logs'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }
}
