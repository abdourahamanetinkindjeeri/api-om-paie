<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TransactionHistoryResource;
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


    public function payMerchant(PaymentRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            $data = $request->validated();
            $metadata = collect($data)->only(['description', 'reference_externe'])->toArray();

            $result = $this->paymentService->payMerchant(
                $user,
                $data['code_marchand'],
                $data['montant'],
                $metadata
            );

            return $this->successResponse(
                new PaymentResource($result),
                'Paiement effectué avec succès'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function checkMerchant(Request $request): JsonResponse
    {
        $request->validate(['code_marchand' => 'required|string']);
        try {
            $exists = $this->paymentService->checkMerchantExists($request->code_marchand);
            $merchantInfo = $exists ? $this->paymentService->getMerchantInfo($request->code_marchand) : null;

            // Ne pas exposer le solde
            if ($merchantInfo) unset($merchantInfo['balance']);

            return $this->successResponse([
                'exists' => $exists,
                'merchant' => $merchantInfo
            ], $exists ? 'Marchand trouvé' : 'Code marchand non trouvé');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }


    public function getMerchantInfo(Request $request): JsonResponse
    {
        $request->validate(['code_marchand' => 'required|string']);
        try {
            $merchantInfo = $this->paymentService->getMerchantInfo($request->code_marchand);

            if (!$merchantInfo) {
                return $this->notFoundResponse('Marchand non trouvé');
            }

            unset($merchantInfo['balance']); // ne pas exposer le solde

            return $this->successResponse(['merchant' => $merchantInfo]);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getMerchantPaymentHistory(Request $request): JsonResponse
    {
        $request->validate(['code_marchand' => 'required|string']);
        try {
            return $this->forbiddenResponse('Fonctionnalité réservée aux marchands authentifiés');

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
            return $this->handleException($e);
        }
    }
}
