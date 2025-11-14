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

    /**
     * @OA\Post(
     *     path="/payment",
     *     summary="Effectuer un paiement vers un marchand",
     *     description="Effectue un paiement d'un utilisateur vers un marchand par code marchand",
     *     operationId="payMerchant",
     *     tags={"Payment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand", "montant"},
     *             @OA\Property(property="code_marchand", type="string", example="BOUT001", description="Code unique du marchand"),
     *             @OA\Property(property="montant", type="number", format="float", example=2500, description="Montant à payer (min: 1, max: 1000000)"),
     *             @OA\Property(property="description", type="string", example="Achat produits", description="Description optionnelle du paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="64f7b1a2c5d4e123456789ae"),
     *                 @OA\Property(property="type", type="string", example="payment"),
     *                 @OA\Property(property="montant", type="number", format="float", example=2500),
     *                 @OA\Property(property="expediteur_telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="destinataire_telephone", type="string", example="+221338901234"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="code_marchand", type="string", example="BOUT001"),
     *                     @OA\Property(property="description", type="string", example="Achat produits")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur métier (solde insuffisant, marchand introuvable, etc.)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/payment/check-merchant",
     *     summary="Vérifier un code marchand",
     *     description="Vérifie si un code marchand existe et est actif, retourne les informations du marchand",
     *     operationId="checkMerchant",
     *     tags={"Payment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand"},
     *             @OA\Property(property="code_marchand", type="string", example="BOUT001", description="Code unique du marchand")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marchand trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="exists", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marchand trouvé"),
     *             @OA\Property(property="merchant", ref="#/components/schemas/Merchant")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="exists", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Marchand non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/payment/merchant-info",
     *     summary="Obtenir les informations d'un marchand",
     *     description="Récupère les informations publiques d'un marchand par son code",
     *     operationId="getMerchantInfo",
     *     tags={"Payment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand"},
     *             @OA\Property(property="code_marchand", type="string", example="BOUT001", description="Code unique du marchand")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informations du marchand récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="merchant", type="object",
     *                 @OA\Property(property="code", type="string", example="BOUT001"),
     *                 @OA\Property(property="name", type="string", example="Boutique Fatou"),
     *                 @OA\Property(property="telephone", type="string", example="+221775551234"),
     *                 @OA\Property(property="email", type="string", example="fatou@boutique.sn"),
     *                 @OA\Property(property="status", type="string", example="active")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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


    /**
     * @OA\Post(
     *     path="/payment/merchant-history",
     *     summary="Historique des paiements d'un marchand",
     *     description="Récupère l'historique des paiements reçus par un marchand, triés par date décroissante (fonctionnalité réservée aux marchands authentifiés - non implémentée)",
     *     operationId="getMerchantPaymentHistory",
     *     tags={"Payment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand"},
     *             @OA\Property(property="code_marchand", type="string", example="BOUT001", description="Code unique du marchand")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Fonctionnalité non disponible",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Fonctionnalité réservée aux marchands authentifiés")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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
