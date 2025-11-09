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
     * Effectuer un transfert
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
     * Vérifier si un numéro existe
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
     * Obtenir le solde de l'utilisateur authentifié
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
     * Obtenir l'historique des transferts
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
