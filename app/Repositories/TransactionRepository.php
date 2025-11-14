<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * Récupère les transactions d'un utilisateur par type
     */
    public function getUserTransactionsByType(string $telephone, string $type, int $page = 1, int $limit = 10): Collection
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user) {
            return collect([]);
        }

        $walletIds = $user->wallets->pluck('id')->toArray();

        if (empty($walletIds)) {
            return collect([]);
        }

        $offset = ($page - 1) * $limit;

        return Transaction::whereIn('wallet_id', $walletIds)
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

        if (!$user) {
            return collect([]);
        }

        $walletIds = $user->wallets->pluck('id')->toArray();

        if (empty($walletIds)) {
            return collect([]);
        }

        $offset = ($page - 1) * $limit;

        return Transaction::whereIn('wallet_id', $walletIds)
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

        if (!$user) {
            return 0;
        }

        $walletIds = $user->wallets->pluck('id')->toArray();

        if (empty($walletIds)) {
            return 0;
        }

        $query = Transaction::whereIn('wallet_id', $walletIds);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->count();
    }

    /**
     * Récupère les transactions d'un compte spécifique
     */
    public function getAccountTransactions(string $telephone, string $walletId, int $page = 1, int $limit = 10): Collection
    {
        // Vérifier que le wallet appartient bien à l'utilisateur
        $user = User::where('telephone', $telephone)->first();
        if (!$user) {
            return collect([]);
        }

        $wallet = $user->wallets()->where('id', $walletId)->first();
        if (!$wallet) {
            return collect([]);
        }

        $offset = ($page - 1) * $limit;

        return Transaction::where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Compte le nombre de transactions d'un compte spécifique
     */
    public function countAccountTransactions(string $telephone, string $walletId): int
    {
        // Vérifier que le wallet appartient bien à l'utilisateur
        $user = User::where('telephone', $telephone)->first();
        if (!$user) {
            return 0;
        }

        $wallet = $user->wallets()->where('id', $walletId)->first();
        if (!$wallet) {
            return 0;
        }

        return Transaction::where('wallet_id', $walletId)->count();
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

        if (!$user) {
            return collect([]);
        }

        $walletIds = $user->wallets->pluck('id')->toArray();

        if (empty($walletIds)) {
            return collect([]);
        }

        $query = Transaction::whereIn('wallet_id', $walletIds)
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

        if (!$user) {
            return collect([]);
        }

        $walletIds = $user->wallets->pluck('id')->toArray();

        if (empty($walletIds)) {
            return collect([]);
        }

        $offset = ($page - 1) * $limit;

        return Transaction::whereIn('wallet_id', $walletIds)
            ->with(['wallet.user', 'logs'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }
}
