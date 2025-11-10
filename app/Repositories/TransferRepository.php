<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

class TransferRepository extends BaseRepository
{
    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    /**
     * Trouver un utilisateur par son numéro de téléphone
     */
    public function findUserByNumber(string $numero): ?User
    {
        // Recherche avec ou sans le préfixe +221
        return User::where('telephone', $numero)
            ->orWhere('telephone', '+221' . $numero)
            ->orWhere('telephone', str_replace('+221', '', $numero))
            ->first();
    }

    /**
     * Obtenir le wallet d'un utilisateur
     */
    public function getUserWallet(string $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    /**
     * Créer une transaction de transfert
     */
    public function createTransferTransaction(array $data): Transaction
    {
        return $this->create([
            'wallet_id' => $data['wallet_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => $data['status'] ?? 'pending',
            'reference' => $data['reference'],
            'meta' => $data['meta'] ?? []
        ]);
    }

    /**
     * Mettre à jour le solde d'un wallet
     */
    public function updateWalletBalance(string $walletId, float $newBalance): bool
    {
        return Wallet::where('id', $walletId)->update(['balance' => $newBalance]);
    }

    /**
     * Obtenir les transactions par référence (pour les transferts liés)
     */
    public function getTransactionsByReference(string $reference): array
    {
        return Transaction::where('reference', $reference)->get()->toArray();
    }

    /**
     * Obtenir l'historique des transferts d'un utilisateur avec tri par date décroissante
     */
    public function getUserTransferHistory(string $userId, int $page = 1, int $limit = 10)
    {
        $wallet = $this->getUserWallet($userId);
        if (!$wallet) return collect([]);

        // Requête avec tri par date décroissante
        $query = $this->model->newQuery()
            ->where('wallet_id', $wallet->id)
            ->whereIn('type', ['transfer'])
            ->orderBy('created_at', 'desc'); // Tri par date décroissante

        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
