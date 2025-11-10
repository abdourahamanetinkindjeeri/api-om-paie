<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransferResource;
use App\Http\Resources\TransferHistoryResource;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class TransferController extends Controller
{
    protected TransferService $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    private function successResponse(array $data, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => true, 'message' => $message], $data), $status);
    }

    private function errorResponse(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    /**
     * @OA\Post(
     *     path="/transfer",
     *     summary="Effectuer un transfert d'argent",
     *     description="Effectue un transfert d'argent entre l'utilisateur authentifié et un destinataire",
     *     operationId="transfer",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "montant"},
     *             @OA\Property(property="telephone", type="string", example="+221781234567", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant à transférer (min: 1, max: 1000000)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="64f7b1a2c5d4e123456789ad"),
     *                 @OA\Property(property="type", type="string", example="transfer"),
     *                 @OA\Property(property="montant", type="number", format="float", example=5000),
     *                 @OA\Property(property="expediteur_telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="destinataire_telephone", type="string", example="+221781234567"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur métier (solde insuffisant, etc.)",
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
    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié via auth()
            $sender = auth()->user();

            if (!$sender) {
                return $this->errorResponse('Utilisateur non authentifié', 401);
            }

            $validated = $request->validated();

            $result = $this->transferService->transfer(
                $sender->telephone,
                $validated['telephone'],
                $validated['montant']
            );

            return $this->successResponse([
                'data' => new TransferResource($result)
            ], 'Transfert effectué avec succès');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/transfer/check-number",
     *     summary="Vérifier l'existence d'un numéro",
     *     description="Vérifie si un numéro de téléphone existe dans le système",
     *     operationId="checkNumber",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numero"},
     *             @OA\Property(property="numero", type="string", example="+221781234567", description="Numéro de téléphone à vérifier")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vérification effectuée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Numéro trouvé"),
     *             @OA\Property(property="exists", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de requête",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function checkNumber(Request $request): JsonResponse
    {
        $request->validate(['numero' => 'required|string']);

        try {
            $exists = $this->transferService->checkUserExists($request->numero);
            return $this->successResponse([
                'exists' => $exists
            ], $exists ? 'Numéro trouvé' : 'Numéro non trouvé');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/transfer/balance",
     *     summary="Obtenir le solde de l'utilisateur",
     *     description="Récupère le solde du portefeuille de l'utilisateur authentifié",
     *     operationId="getBalance",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès"),
     *             @OA\Property(property="solde", type="number", format="float", example=25000.50),
     *             @OA\Property(property="numero", type="string", example="+221771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Impossible de récupérer le solde",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getBalance(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->errorResponse('Utilisateur non authentifié', 401);
            }

            $balance = $this->transferService->getUserBalance($user->telephone);

            if ($balance === null) {
                return $this->errorResponse('Impossible de récupérer le solde');
            }

            return $this->successResponse([
                'solde' => $balance,
                'numero' => $user->telephone
            ], 'Solde récupéré avec succès');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/transfer/history",
     *     summary="Historique des transferts",
     *     description="Récupère l'historique des transferts de l'utilisateur authentifié (envoyés et reçus), triés par date décroissante (du plus récent au plus ancien)",
     *     operationId="getTransferHistory",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", example=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10, default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès (triés par date décroissante - plus récents en premier)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Historique récupéré avec succès"),
     *             @OA\Property(property="transfers", type="array",
     *                 @OA\Items(ref="#/components/schemas/Transaction")
     *             ),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getTransferHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->errorResponse('Utilisateur non authentifié', 401);
            }

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);

            $history = $this->transferService->getUserTransferHistory($user->telephone, $page, $limit);

            return $this->successResponse([
                'transfers' => TransferHistoryResource::collection(collect($history['transfers'])),
                'pagination' => $history['pagination']
            ], 'Historique récupéré avec succès');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
