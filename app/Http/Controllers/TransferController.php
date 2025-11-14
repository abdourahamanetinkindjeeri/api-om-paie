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
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            $validated = $request->validated();

            $result = $this->transferService->transfer(
                $sender->telephone,
                $validated['telephone'],
                $validated['montant']
            );

            return $this->successResponse(
                new TransferResource($result),
                'Transfert effectué avec succès'
            );
        } catch (Exception $e) {
            return $this->handleException($e);
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
            return $this->handleException($e);
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
                return $this->unauthorizedResponse('Utilisateur non authentifié');
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
            return $this->handleException($e);
        }
    }

}
