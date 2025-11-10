<?php

namespace App\Services;

use App\Services\Contracts\HistoryServiceInterface;
use App\Repositories\TransactionRepository;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class HistoryService implements HistoryServiceInterface
{
    public function __construct(
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Récupère l'historique combiné des paiements et transferts d'un utilisateur
     */
    public function getUserHistory(string $telephone, int $page = 1, int $limit = 10): array
    {
        try {
            $transactions = $this->transactionRepository->getUserTransactionsWithRelations($telephone, $page, $limit);
            $totalCount = $this->transactionRepository->countUserTransactions($telephone);

            $history = $transactions->map(function ($transaction) {
                return $this->formatTransaction($transaction);
            });

            $pagination = $this->calculatePagination($totalCount, $page, $limit);

            return [
                'transactions' => $history,
                'pagination' => $pagination
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);

            return [
                'transactions' => [],
                'pagination' => $this->calculatePagination(0, $page, $limit)
            ];
        }
    }

    /**
     * Récupère l'historique des transferts d'un utilisateur
     */
    public function getUserTransferHistory(string $telephone, int $page = 1, int $limit = 10): array
    {
        try {
            $transfers = $this->transactionRepository->getUserTransactionsByType($telephone, 'transfer', $page, $limit);
            $totalCount = $this->transactionRepository->countUserTransactions($telephone, 'transfer');

            $history = $transfers->map(function ($transfer) {
                return $this->formatTransaction($transfer, 'transfer');
            });

            $pagination = $this->calculatePagination($totalCount, $page, $limit);

            return [
                'transfers' => $history,
                'pagination' => $pagination
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des transferts', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);

            return [
                'transfers' => [],
                'pagination' => $this->calculatePagination(0, $page, $limit)
            ];
        }
    }

    /**
     * Récupère l'historique des paiements d'un utilisateur
     */
    public function getUserPaymentHistory(string $telephone, int $page = 1, int $limit = 10): array
    {
        try {
            $payments = $this->transactionRepository->getUserTransactionsByType($telephone, 'payment', $page, $limit);
            $totalCount = $this->transactionRepository->countUserTransactions($telephone, 'payment');

            $history = $payments->map(function ($payment) {
                return $this->formatTransaction($payment, 'payment');
            });

            $pagination = $this->calculatePagination($totalCount, $page, $limit);

            return [
                'payments' => $history,
                'pagination' => $pagination
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des paiements', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);

            return [
                'payments' => [],
                'pagination' => $this->calculatePagination(0, $page, $limit)
            ];
        }
    }

    /**
     * Formate une transaction pour l'affichage
     */
    private function formatTransaction(Transaction $transaction, string $type = null): array
    {
        $baseFormat = [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
            'reference' => $transaction->reference,
            'date' => $transaction->created_at->format('Y-m-d H:i:s'),
            'timestamp' => $transaction->created_at->timestamp,
            'meta' => $transaction->meta ?? []
        ];

        // Ajouter des informations spécifiques selon le type
        switch ($transaction->type) {
            case 'transfer':
                return array_merge($baseFormat, [
                    'recipient_phone' => $transaction->meta['recipient_phone'] ?? null,
                    'sender_phone' => $transaction->meta['sender_phone'] ?? null,
                    'description' => 'Transfert d\'argent'
                ]);

            case 'payment':
                return array_merge($baseFormat, [
                    'merchant_code' => $transaction->meta['merchant_code'] ?? null,
                    'merchant_name' => $transaction->meta['merchant_name'] ?? null,
                    'description' => $transaction->meta['description'] ?? 'Paiement marchand'
                ]);

            default:
                return array_merge($baseFormat, [
                    'description' => ucfirst($transaction->type)
                ]);
        }
    }

    /**
     * Calcule les informations de pagination
     */
    private function calculatePagination(int $totalCount, int $page, int $limit): array
    {
        $totalPages = ceil($totalCount / $limit);
        $hasNext = $page < $totalPages;
        $hasPrevious = $page > 1;

        return [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $totalCount,
            'total_pages' => $totalPages,
            'has_next' => $hasNext,
            'has_previous' => $hasPrevious,
            'next_page' => $hasNext ? $page + 1 : null,
            'previous_page' => $hasPrevious ? $page - 1 : null
        ];
    }
}
