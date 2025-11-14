<?php

namespace App\Http\Controllers;

use App\Repositories\WalletRepository;
use App\Services\TransferService;
use App\Services\PaymentService;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\TransferResource;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class ComptesController extends Controller
{
    protected WalletRepository $walletRepository;
    protected TransferService $transferService;
    protected PaymentService $paymentService;

    public function __construct(
        WalletRepository $walletRepository,
        TransferService $transferService,
        PaymentService $paymentService
    ) {
        $this->walletRepository = $walletRepository;
        $this->transferService = $transferService;
        $this->paymentService = $paymentService;
    }

    /**
     * @OA\Get(
     *     path="/comptes/{numeroCompte}",
     *     summary="Obtenir les informations d'un compte",
     *     description="Récupère les informations d'un compte spécifique (solde, etc.)",
     *     operationId="getCompteInfo",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte (format: Principal{timestamp} ou Secondaire{timestamp})",
     *         @OA\Schema(type="string", example="Principal1731580000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informations du compte récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Informations du compte récupérées avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="numero_compte", type="string", example="a05ab654-1a7e-47ca-a9f0-3af7926e5e82"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1947500),
     *                 @OA\Property(property="devise", type="string", example="XOF")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - compte n'appartient pas à l'utilisateur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getBalance(string $numeroCompte): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            $wallet = null;

            // Vérifier si c'est un numéro de compte formaté (Principal/Secondaire + timestamp)
            if (preg_match('/^(Principal|Secondaire)(\d+)$/', $numeroCompte, $matches)) {
                $type = $matches[1] === 'Principal' ? true : false;
                $timestamp = $matches[2];

                // Trouver le wallet par user_id, is_main, et timestamp proche
                $wallets = $this->walletRepository->getUserWallets($user->id);
                foreach ($wallets as $w) {
                    $walletTimestamp = strtotime($w['created_at']);
                    if (($w['is_main'] ?? false) === $type && $walletTimestamp == $timestamp) {
                        $wallet = $this->walletRepository->find($w['id']);
                        break;
                    }
                }
            } else {
                // Ancien format UUID
                $wallet = $this->walletRepository->find($numeroCompte);
            }

            if (!$wallet) {
                return $this->notFoundResponse('Compte non trouvé');
            }

            // Vérifier que le wallet appartient à l'utilisateur authentifié
            if ($wallet->user_id !== $user->id) {
                return $this->forbiddenResponse('Accès refusé - compte n\'appartient pas à l\'utilisateur');
            }

            $type = ($wallet->is_main ?? false) ? 'principal' : 'secondaire';
            $prefix = $type === 'principal' ? 'Principal' : 'Secondaire';
            $timestamp = strtotime($wallet->created_at);
            $formattedNumero = $prefix . $timestamp;

            return $this->successResponse([
                'numero_compte' => $formattedNumero,
                'solde' => $wallet->balance,
                'devise' => $wallet->currency,
                'type' => $type
            ], 'Informations du compte récupérées avec succès');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister tous les comptes de l'utilisateur",
     *     description="Récupère la liste de tous les comptes (principal et secondaires) de l'utilisateur authentifié",
     *     operationId="getUserComptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Comptes récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="comptes", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="numero_compte", type="string", example="a05ab654-1a7e-47ca-a9f0-3af7926e5e82"),
     *                     @OA\Property(property="solde", type="number", format="float", example=1947500),
     *                     @OA\Property(property="devise", type="string", example="XOF"),
     *                     @OA\Property(property="type", type="string", example="principal")
     *                 )
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
    public function getUserComptes(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            $wallets = $this->walletRepository->getUserWallets($user->id);

            $comptes = array_map(function ($wallet) {
                $type = (isset($wallet['is_main']) && $wallet['is_main']) ? 'principal' : 'secondaire';
                $prefix = $type === 'principal' ? 'Principal' : 'Secondaire';
                $timestamp = strtotime($wallet['created_at']);
                $numero_compte = $prefix . $timestamp;

                return [
                    'numero_compte' => $numero_compte,
                    'solde' => $wallet['balance'],
                    'devise' => $wallet['currency'],
                    'type' => $type
                ];
            }, $wallets);

            return $this->successResponse([
                'comptes' => $comptes
            ], 'Comptes récupérés avec succès');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/{numeroCompte}/balance",
     *     summary="Obtenir le solde d'un compte",
     *     description="Récupère uniquement le solde d'un compte spécifique",
     *     operationId="getAccountBalance",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte (format: Principal{timestamp} ou Secondaire{timestamp})",
     *         @OA\Schema(type="string", example="Principal1731580000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès"),
     *             @OA\Property(property="solde", type="number", format="float", example=1947500),
     *             @OA\Property(property="numero_compte", type="string", example="Principal1731580000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - compte n'appartient pas à l'utilisateur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getAccountBalance(string $numeroCompte): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            $wallet = null;

            // Vérifier si c'est un numéro de compte formaté (Principal/Secondaire + timestamp)
            if (preg_match('/^(Principal|Secondaire)(\d+)$/', $numeroCompte, $matches)) {
                $type = $matches[1] === 'Principal' ? true : false;
                $timestamp = $matches[2];

                // Trouver le wallet par user_id, is_main, et timestamp proche
                $wallets = $this->walletRepository->getUserWallets($user->id);
                foreach ($wallets as $w) {
                    $walletTimestamp = strtotime($w['created_at']);
                    if (($w['is_main'] ?? false) === $type && $walletTimestamp == $timestamp) {
                        $wallet = $this->walletRepository->find($w['id']);
                        break;
                    }
                }
            } else {
                // Ancien format UUID
                $wallet = $this->walletRepository->find($numeroCompte);
            }

            if (!$wallet) {
                return $this->notFoundResponse('Compte non trouvé');
            }

            // Vérifier que le wallet appartient à l'utilisateur authentifié
            if ($wallet->user_id !== $user->id) {
                return $this->forbiddenResponse('Accès refusé - compte n\'appartient pas à l\'utilisateur');
            }

            $type = ($wallet->is_main ?? false) ? 'principal' : 'secondaire';
            $prefix = $type === 'principal' ? 'Principal' : 'Secondaire';
            $timestamp = strtotime($wallet->created_at);
            $formattedNumero = $prefix . $timestamp;

            return $this->successResponse([
                'solde' => $wallet->balance,
                'numero_compte' => $formattedNumero
            ], 'Solde récupéré avec succès');

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/{numeroCompte}/history",
     *     summary="Obtenir l'historique d'un compte",
     *     description="Récupère l'historique des transactions (transferts et paiements) pour un compte spécifique",
     *     operationId="getAccountHistory",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte (format: Principal{timestamp} ou Secondaire{timestamp})",
     *         @OA\Schema(type="string", example="Principal1731580000")
     *     ),
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
     *         description="Historique du compte récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Historique du compte récupéré avec succès"),
     *             @OA\Property(property="transactions", type="array",
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
     *         response=403,
     *         description="Accès refusé - compte n'appartient pas à l'utilisateur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getAccountHistory(string $numeroCompte, Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Trouver le wallet
            $wallet = $this->findWalletByNumeroCompte($numeroCompte, $user->id);
            if (!$wallet) {
                return $this->notFoundResponse('Compte non trouvé');
            }

            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));

            // Utiliser le HistoryService pour récupérer l'historique filtré par wallet
            $historyService = app(\App\Services\Contracts\HistoryServiceInterface::class);
            $history = $historyService->getAccountHistory($user->telephone, $wallet->id, $page, $limit);

            return $this->paginatedResponse(
                \App\Http\Resources\TransactionHistoryResource::collection(collect($history['transactions'])),
                $history['pagination'],
                'Historique du compte récupéré avec succès'
            );

        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{numeroCompte}/transfer",
     *     summary="Effectuer un transfert depuis un compte spécifique",
     *     description="Effectue un transfert d'argent depuis le compte spécifié vers un destinataire",
     *     operationId="transferFromAccount",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte expéditeur (format: Principal{timestamp} ou Secondaire{timestamp})",
     *         @OA\Schema(type="string", example="Principal1731580000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "montant"},
     *             @OA\Property(property="telephone", type="string", example="+221781234567", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant à transférer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - compte n'appartient pas à l'utilisateur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function transferFromAccount(string $numeroCompte, TransferRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Trouver le wallet expéditeur
            $senderWallet = $this->findWalletByNumeroCompte($numeroCompte, $user->id);
            if (!$senderWallet) {
                return $this->notFoundResponse('Compte expéditeur non trouvé');
            }

            $validated = $request->validated();

            $result = $this->transferService->transfer(
                $user->telephone,
                $validated['telephone'],
                $validated['montant'],
                [], // metadata
                $senderWallet->id // senderWalletId
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
     *     path="/comptes/{numeroCompte}/payment",
     *     summary="Effectuer un paiement depuis un compte spécifique",
     *     description="Effectue un paiement vers un marchand depuis le compte spécifié",
     *     operationId="payFromAccount",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte payeur (format: Principal{timestamp} ou Secondaire{timestamp})",
     *         @OA\Schema(type="string", example="Principal1731580000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand", "montant"},
     *             @OA\Property(property="code_marchand", type="string", example="M001", description="Code du marchand"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant à payer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - compte n'appartient pas à l'utilisateur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function payFromAccount(string $numeroCompte, PaymentRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Trouver le wallet payeur
            $userWallet = $this->findWalletByNumeroCompte($numeroCompte, $user->id);
            if (!$userWallet) {
                return $this->notFoundResponse('Compte payeur non trouvé');
            }

            $validated = $request->validated();

            $result = $this->paymentService->payMerchant(
                $user,
                $validated['code_marchand'],
                $validated['montant'],
                [], // metadata
                $userWallet->id // userWalletId
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
     * Helper method to find wallet by numeroCompte
     */
    private function findWalletByNumeroCompte(string $numeroCompte, string $userId): ?object
    {
        // Vérifier si c'est un numéro de compte formaté (Principal/Secondaire + timestamp)
        if (preg_match('/^(Principal|Secondaire)(\d+)$/', $numeroCompte, $matches)) {
            $type = $matches[1] === 'Principal' ? true : false;
            $timestamp = $matches[2];

            // Trouver le wallet par user_id, is_main, et timestamp proche
            $wallets = $this->walletRepository->getUserWallets($userId);
            foreach ($wallets as $w) {
                $walletTimestamp = strtotime($w['created_at']);
                if (($w['is_main'] ?? false) === $type && $walletTimestamp == $timestamp) {
                    return $this->walletRepository->find($w['id']);
                }
            }
        } else {
            // Ancien format UUID
            $wallet = $this->walletRepository->find($numeroCompte);
            if ($wallet && $wallet->user_id === $userId) {
                return $wallet;
            }
        }

        return null;
    }
}
