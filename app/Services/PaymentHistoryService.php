<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;

/**
 * Service spécialisé pour la gestion des historiques de paiement
 */
class PaymentHistoryService
{
    protected PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Récupère l'historique des paiements d'un utilisateur
     */
    public function getUserPaymentHistory(string $userPhone, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $user = $this->paymentRepository->findUserByPhone($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        return $this->paymentRepository->getUserPaymentHistory($user->id, $page, $limit);
    }

    /**
     * Récupère l'historique des paiements reçus par un marchand
     */
    public function getMerchantPaymentHistory(string $merchantCode, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
        if (!$merchant) {
            throw new Exception('Marchand non trouvé');
        }

        return $this->paymentRepository->getMerchantPaymentHistory($merchant->id, $page, $limit);
    }

    /**
     * Récupère les statistiques de paiement d'un utilisateur
     */
    public function getUserPaymentStats(string $userPhone): array
    {
        $user = $this->paymentRepository->findUserByPhone($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        $wallet = $this->paymentRepository->getUserWallet($user->id);
        if (!$wallet) {
            return [
                'total_payments' => 0,
                'total_amount' => 0,
                'last_payment_date' => null,
                'average_payment' => 0
            ];
        }

        // Récupérer toutes les transactions de paiement pour cet utilisateur
        $transactions = $this->paymentRepository->getModel()->newQuery()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'payment')
            ->where('amount', '<', 0) // Transactions de débit uniquement
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPayments = $transactions->count();
        $totalAmount = abs($transactions->sum('amount')); // Somme positive des débits

        $lastPaymentDate = $transactions->first()?->created_at;

        $averagePayment = $totalPayments > 0 ? $totalAmount / $totalPayments : 0;

        return [
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount,
            'last_payment_date' => $lastPaymentDate,
            'average_payment' => round($averagePayment, 2)
        ];
    }

    /**
     * Récupère les statistiques de paiement d'un marchand
     */
    public function getMerchantPaymentStats(string $merchantCode): array
    {
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
        if (!$merchant) {
            throw new Exception('Marchand non trouvé');
        }

        $wallet = $this->paymentRepository->getMerchantWallet($merchant->id);
        if (!$wallet) {
            return [
                'total_payments_received' => 0,
                'total_amount_received' => 0,
                'last_payment_date' => null,
                'average_payment' => 0
            ];
        }

        // Récupérer toutes les transactions de paiement pour ce marchand
        $transactions = $this->paymentRepository->getModel()->newQuery()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'payment')
            ->where('amount', '>', 0) // Transactions de crédit uniquement
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPayments = $transactions->count();
        $totalAmount = $transactions->sum('amount');

        $lastPaymentDate = $transactions->first()?->created_at;

        $averagePayment = $totalPayments > 0 ? $totalAmount / $totalPayments : 0;

        return [
            'total_payments_received' => $totalPayments,
            'total_amount_received' => $totalAmount,
            'last_payment_date' => $lastPaymentDate,
            'average_payment' => round($averagePayment, 2)
        ];
    }

    /**
     * Recherche des paiements par référence
     */
    public function searchPaymentsByReference(string $reference, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        return $this->paymentRepository->getModel()->newQuery()
            ->where('reference', 'like', "%{$reference}%")
            ->orWhere('meta.payment_reference', 'like', "%{$reference}%")
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Récupère les paiements d'un utilisateur dans une période donnée
     */
    public function getUserPaymentsInPeriod(string $userPhone, string $startDate, string $endDate, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $user = $this->paymentRepository->findUserByPhone($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        $wallet = $this->paymentRepository->getUserWallet($user->id);
        if (!$wallet) {
            throw new Exception('Wallet utilisateur non trouvé');
        }

        return $this->paymentRepository->getModel()->newQuery()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'payment')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Récupère les paiements d'un marchand dans une période donnée
     */
    public function getMerchantPaymentsInPeriod(string $merchantCode, string $startDate, string $endDate, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
        if (!$merchant) {
            throw new Exception('Marchand non trouvé');
        }

        $wallet = $this->paymentRepository->getMerchantWallet($merchant->id);
        if (!$wallet) {
            throw new Exception('Wallet marchand non trouvé');
        }

        return $this->paymentRepository->getModel()->newQuery()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'payment')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Exporte l'historique des paiements d'un utilisateur (résumé)
     */
    public function exportUserPaymentHistory(string $userPhone, string $startDate = null, string $endDate = null): array
    {
        $user = $this->paymentRepository->findUserByPhone($userPhone);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        $query = $this->paymentRepository->getModel()->newQuery()
            ->where('type', 'payment')
            ->where('amount', '<', 0); // Débits uniquement

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $wallet = $this->paymentRepository->getUserWallet($user->id);
        if ($wallet) {
            $query->where('wallet_id', $wallet->id);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return $transactions->map(function ($transaction) {
            return [
                'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                'reference' => $transaction->reference,
                'amount' => abs($transaction->amount),
                'merchant' => $transaction->meta['merchant_name'] ?? 'N/A',
                'status' => $transaction->status
            ];
        })->toArray();
    }
}
