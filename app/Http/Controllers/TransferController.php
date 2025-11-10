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

    private function successResponse(array $data, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => true, 'message' => $message], $data), $status);
    }

    private function errorResponse(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $sender = auth()->user();

            if (!$sender) {
                return $this->errorResponse('Utilisateur non authentifié', 401);
            }

            $result = $this->transferService->transfer(
                $sender->telephone,
                $request->input('telephone'),
                $request->input('montant')
            );

            return $this->successResponse([
                'data' => new TransferResource($result)
            ], 'Transfert effectué avec succès');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

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
                'data' => $history
            ], 'Historique récupéré avec succès');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
