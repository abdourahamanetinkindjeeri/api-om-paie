<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Effectuer un paiement vers un marchand
     */
    public function payMerchant(PaymentRequest $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié (payeur)
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Préparer les métadonnées optionnelles
            $validated = $request->validated();
            $metadata = [];
            if (!empty($validated['description'])) {
                $metadata['description'] = $validated['description'];
            }
            if (!empty($validated['reference_externe'])) {
                $metadata['reference_externe'] = $validated['reference_externe'];
            }

            // Effectuer le paiement
            $result = $this->paymentService->payMerchant(
                $user,
                $request->validated()['code_marchand'],
                $request->validated()['montant'],
                $metadata
            );

            return response()->json([
                'success' => true,
                'message' => 'Paiement effectué avec succès',
                'data' => new PaymentResource($result)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Vérifier si un code marchand existe
     */
    public function checkMerchant(Request $request): JsonResponse
    {
        $request->validate([
            'code_marchand' => 'required|string'
        ]);

        try {
            $exists = $this->paymentService->checkMerchantExists($request->code_marchand);
            $merchantInfo = null;

            if ($exists) {
                $merchantInfo = $this->paymentService->getMerchantInfo($request->code_marchand);
            }

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Marchand trouvé' : 'Code marchand non trouvé',
                'merchant' => $merchantInfo
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtenir les informations d'un marchand
     */
    public function getMerchantInfo(Request $request): JsonResponse
    {
        $request->validate([
            'code_marchand' => 'required|string'
        ]);

        try {
            $merchantInfo = $this->paymentService->getMerchantInfo($request->code_marchand);

            if (!$merchantInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Marchand non trouvé'
                ], 404);
            }

            // Ne pas exposer le solde dans les infos publiques
            unset($merchantInfo['balance']);

            return response()->json([
                'success' => true,
                'merchant' => $merchantInfo
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtenir l'historique des paiements de l'utilisateur authentifié
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

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);

            $history = $this->paymentService->getUserPaymentHistory($user->telephone, $page, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Historique des paiements récupéré avec succès',
                'data' => $history
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtenir l'historique des paiements reçus (pour les marchands)
     * Note: Cette méthode nécessiterait une authentification spécifique aux marchands
     */
    public function getMerchantPaymentHistory(Request $request): JsonResponse
    {
        $request->validate([
            'code_marchand' => 'required|string'
        ]);

        try {
            // TODO: Implémenter l'authentification marchand
            // Pour l'instant, nous retournons une erreur
            return response()->json([
                'success' => false,
                'message' => 'Fonctionnalité réservée aux marchands authentifiés'
            ], 403);

            // Code pour plus tard quand l'auth marchand sera implémentée :
            /*
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);

            $history = $this->paymentService->getMerchantPaymentHistory(
                $request->code_marchand,
                $page,
                $limit
            );

            return response()->json([
                'success' => true,
                'message' => 'Historique des paiements du marchand récupéré avec succès',
                'data' => $history
            ], 200);
            */
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
