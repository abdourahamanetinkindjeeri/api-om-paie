<?php

namespace App\Http\Controllers;

use App\Services\Contracts\HistoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller
{
    public function __construct(
        private HistoryServiceInterface $historyService
    ) {}

    /**
     * Récupère l'historique complet (paiements + transferts) de l'utilisateur connecté
     * Trié par date décroissante
     */
    public function getUserHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));

            $history = $this->historyService->getUserHistory($user->telephone, $page, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Historique récupéré avec succès',
                'data' => $history
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique utilisateur', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique'
            ], 500);
        }
    }

    /**
     * Récupère uniquement l'historique des transferts de l'utilisateur connecté
     */
    public function getUserTransferHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));

            $history = $this->historyService->getUserTransferHistory($user->telephone, $page, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Historique des transferts récupéré avec succès',
                'data' => $history
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des transferts', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique des transferts'
            ], 500);
        }
    }

    /**
     * Récupère uniquement l'historique des paiements de l'utilisateur connecté
     */
    public function getUserPaymentHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));

            $history = $this->historyService->getUserPaymentHistory($user->telephone, $page, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Historique des paiements récupéré avec succès',
                'data' => $history
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des paiements', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique des paiements'
            ], 500);
        }
    }

    /**
     * Statistiques rapides de l'utilisateur
     */
    public function getUserStats(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Récupérer les dernières transactions pour calculer les stats
            $recentHistory = $this->historyService->getUserHistory($user->telephone, 1, 100);
            $transactions = $recentHistory['transactions'] ?? [];

            $stats = [
                'total_transactions' => $recentHistory['pagination']['total'] ?? 0,
                'total_transfers' => 0,
                'total_payments' => 0,
                'total_amount_transferred' => 0,
                'total_amount_paid' => 0,
                'recent_transactions_count' => count($transactions)
            ];

            foreach ($transactions as $transaction) {
                if ($transaction['type'] === 'transfer') {
                    $stats['total_transfers']++;
                    $stats['total_amount_transferred'] += $transaction['amount'];
                } elseif ($transaction['type'] === 'payment') {
                    $stats['total_payments']++;
                    $stats['total_amount_paid'] += $transaction['amount'];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées avec succès',
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}
