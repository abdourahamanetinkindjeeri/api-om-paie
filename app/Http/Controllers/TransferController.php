<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransferResource;
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
     *     description="Transfère de l'argent du compte de l'utilisateur connecté vers un autre utilisateur",
     *     operationId="transfer",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "montant"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567", description="Numéro du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant à transférer en FCFA")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="montant", type="number"),
     *                 @OA\Property(property="expediteur", type="string"),
     *                 @OA\Property(property="destinataire", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur lors du transfert",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié (expéditeur)
            $sender = auth()->user();

            if (!$sender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Effectuer le transfert
            $result = $this->transferService->transfer(
                $sender->telephone,
                $request->validated()['telephone'],
                $request->validated()['montant']
            );

            return response()->json([
                'success' => true,
                'message' => 'Transfert effectué avec succès',
                'data' => new TransferResource($result)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/transfer/check-number",
     *     summary="Vérifier l'existence d'un numéro",
     *     description="Vérifie si un numéro de téléphone est enregistré dans le système",
     *     operationId="checkNumber",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numero"},
     *             @OA\Property(property="numero", type="string", example="+221771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vérification effectuée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="exists", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function checkNumber(Request $request): JsonResponse
    {
        $request->validate([
            'numero' => 'required|string'
        ]);

        try {
            $exists = $this->transferService->checkUserExists($request->numero);

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Numéro trouvé' : 'Numéro non trouvé'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/transfer/balance",
     *     summary="Obtenir le solde utilisateur",
     *     description="Récupère le solde du portefeuille de l'utilisateur connecté",
     *     operationId="getBalance",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="solde", type="number", format="float", example=25000),
     *             @OA\Property(property="numero", type="string", example="+221771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur lors de la récupération du solde",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function getBalance(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $balance = $this->transferService->getUserBalance($user->telephone);

            if ($balance === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de récupérer le solde'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'solde' => $balance,
                'numero' => $user->telephone
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/transfer/history",
     *     summary="Obtenir l'historique des transferts",
     *     description="Récupère l'historique des transferts (envoyés et reçus) de l'utilisateur connecté",
     *     operationId="getTransferHistory",
     *     tags={"Transfer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transactions", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="string"),
     *                         @OA\Property(property="montant", type="number"),
     *                         @OA\Property(property="type", type="string"),
     *                         @OA\Property(property="status", type="string"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function getTransferHistory(Request $request): JsonResponse
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

            $history = $this->transferService->getUserTransferHistory($user->telephone, $page, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Historique récupéré avec succès',
                'data' => $history
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
