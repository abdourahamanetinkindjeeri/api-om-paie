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
