<?php

namespace App\Services;

use App\Repositories\TransferRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;

/**
 * Service spécialisé pour la gestion des historiques de transfert
 */
class TransferHistoryService
{
    protected TransferRepository $transferRepository;

    public function __construct(TransferRepository $transferRepository)
    {
        $this->transferRepository = $transferRepository;
    }

    /**
     * Récupère l'historique des transferts d'un utilisateur
     */
    public function getUserTransferHistory(string $userPhone, int $page = 1, int $limit = 10): array
    {
        $user = $this->transferRepository->findUserByNumber($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        // Utiliser la nouvelle méthode avec tri par date décroissante
        $result = $this->transferRepository->getUserTransferHistory($user->id, $page, $limit);

        // Retourner un tableau avec les données paginées
        return [
            'transfers' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage()
            ]
        ];
    }

    /**
     * Récupère les statistiques de transfert d'un utilisateur
     */
    public function getUserTransferStats(string $userPhone): array
    {
        $user = $this->transferRepository->findUserByNumber($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        $wallet = $this->transferRepository->getUserWallet($user->id);
        if (!$wallet) {
            return [
                'total_transfers_sent' => 0,
                'total_transfers_received' => 0,
                'total_amount_sent' => 0,
                'total_amount_received' => 0,
                'last_transfer_date' => null
            ];
        }

        // Récupérer toutes les transactions de transfert pour cet utilisateur
        $transactions = $this->transferRepository->getModel()->newQuery()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'transfer')
            ->orderBy('created_at', 'desc')
            ->get();

        $sentTransactions = $transactions->where('amount', '<', 0);
        $receivedTransactions = $transactions->where('amount', '>', 0);

        $totalSent = $sentTransactions->count();
        $totalReceived = $receivedTransactions->count();
        $amountSent = abs($sentTransactions->sum('amount'));
        $amountReceived = $receivedTransactions->sum('amount');

        $lastTransferDate = $transactions->first()?->created_at;

        return [
            'total_transfers_sent' => $totalSent,
            'total_transfers_received' => $totalReceived,
            'total_amount_sent' => $amountSent,
            'total_amount_received' => $amountReceived,
            'last_transfer_date' => $lastTransferDate
        ];
    }

    /**
     * Recherche des transferts par référence
     */
    public function searchTransfersByReference(string $reference, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        return $this->transferRepository->getModel()->newQuery()
            ->where('reference', 'like', "%{$reference}%")
            ->orWhere('meta.transfer_reference', 'like', "%{$reference}%")
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Récupère les transferts d'un utilisateur dans une période donnée
     */
    public function getUserTransfersInPeriod(string $userPhone, string $startDate, string $endDate, int $page = 1, int $limit = 10): array
    {
        $user = $this->transferRepository->findUserByNumber($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        $wallet = $this->transferRepository->getUserWallet($user->id);
        if (!$wallet) {
            throw new Exception('Wallet utilisateur non trouvé');
        }

        $result = $this->transferRepository->getModel()->newQuery()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'transfer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'transfers' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage()
            ]
        ];
    }

    /**
     * Récupère les transferts entre deux utilisateurs spécifiques
     */
    public function getTransfersBetweenUsers(string $senderPhone, string $receiverPhone, int $page = 1, int $limit = 10): array
    {
        $sender = $this->transferRepository->findUserByNumber($senderPhone);
        $receiver = $this->transferRepository->findUserByNumber($receiverPhone);

        if (!$sender || !$receiver) {
            throw new Exception('Un des utilisateurs n\'existe pas');
        }

        $senderWallet = $this->transferRepository->getUserWallet($sender->id);
        if (!$senderWallet) {
            throw new Exception('Wallet expéditeur non trouvé');
        }

        $result = $this->transferRepository->getModel()->newQuery()
            ->where('wallet_id', $senderWallet->id)
            ->where('type', 'transfer')
            ->where('amount', '<', 0) // Transactions de débit uniquement
            ->where('meta.sender_number', $senderPhone)
            ->where('meta.receiver_number', $receiverPhone)
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'transfers' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage()
            ]
        ];
    }

    /**
     * Exporte l'historique des transferts d'un utilisateur (résumé)
     */
    public function exportUserTransferHistory(string $userPhone, string $startDate = null, string $endDate = null): array
    {
        $user = $this->transferRepository->findUserByNumber($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        $query = $this->transferRepository->getModel()->newQuery()
            ->where('type', 'transfer');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $wallet = $this->transferRepository->getUserWallet($user->id);
        if ($wallet) {
            $query->where('wallet_id', $wallet->id);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return $transactions->map(function ($transaction) {
            $isDebit = $transaction->amount < 0;
            return [
                'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                'reference' => $transaction->reference,
                'type' => $isDebit ? 'envoi' : 'réception',
                'amount' => abs($transaction->amount),
                'counterparty' => $isDebit ?
                    ($transaction->meta['receiver_number'] ?? 'N/A') :
                    ($transaction->meta['sender_number'] ?? 'N/A'),
                'status' => $transaction->status
            ];
        })->toArray();
    }
}
