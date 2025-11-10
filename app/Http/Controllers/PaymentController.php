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
     * Effectuer un paiement marchand
     */
    public function payMerchant(PaymentRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Utilisateur non authentifié'], 401);
            }

            $data = $request->validated();
            $metadata = collect($data)->only(['description', 'reference_externe'])->toArray();

            $result = $this->paymentService->payMerchant(
                $user,
                $data['code_marchand'],
                $data['montant'],
                $metadata
            );

            return response()->json([
                'success' => true,
                'message' => 'Paiement effectué avec succès',
                'data' => new PaymentResource($result)
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Vérifier l’existence d’un marchand
     */
    public function checkMerchant(Request $request): JsonResponse
    {
        $request->validate(['code_marchand' => 'required|string']);
        try {
            $exists = $this->paymentService->checkMerchantExists($request->code_marchand);
            $merchantInfo = $exists ? $this->paymentService->getMerchantInfo($request->code_marchand) : null;

            // Ne pas exposer le solde
            if ($merchantInfo) unset($merchantInfo['balance']);

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Marchand trouvé' : 'Code marchand non trouvé',
                'merchant' => $merchantInfo
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Obtenir les informations d’un marchand
     */
    public function getMerchantInfo(Request $request): JsonResponse
    {
        $request->validate(['code_marchand' => 'required|string']);
        try {
            $merchantInfo = $this->paymentService->getMerchantInfo($request->code_marchand);

            if (!$merchantInfo) {
                return response()->json(['success' => false, 'message' => 'Marchand non trouvé'], 404);
            }

            unset($merchantInfo['balance']); // ne pas exposer le solde

            return response()->json(['success' => true, 'merchant' => $merchantInfo], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Historique des paiements de l'utilisateur connecté
     */
    public function getUserPaymentHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Utilisateur non authentifié'], 401);
            }

            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);

            $history = $this->paymentService->getUserPaymentHistory($user->telephone, $page, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Historique des paiements récupéré avec succès',
                'data' => PaymentResource::collection($history)
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Historique des paiements reçus par un marchand
     * TODO: Authentification marchands à implémenter
     */
    public function getMerchantPaymentHistory(Request $request): JsonResponse
    {
        $request->validate(['code_marchand' => 'required|string']);
        try {
            return response()->json([
                'success' => false,
                'message' => 'Fonctionnalité réservée aux marchands authentifiés'
            ], 403);

            // Quand auth marchand implémentée :
            /*
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);

            $history = $this->paymentService->getMerchantPaymentHistory($request->code_marchand, $page, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Historique des paiements du marchand récupéré avec succès',
                'data' => PaymentResource::collection($history)
            ], 200);
            */
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
