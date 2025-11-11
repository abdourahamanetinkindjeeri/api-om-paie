<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AuthResource;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Inscription d'un nouvel utilisateur",
     *     description="Crée un nouveau compte utilisateur avec les informations personnelles",
     *     operationId="register",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "telephone", "code", "code_confirmation"},
     *             @OA\Property(property="nom", type="string", example="Diop"),
     *             @OA\Property(property="prenom", type="string", example="Mamadou"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="code", type="string", example="1234"),
     *             @OA\Property(property="code_confirmation", type="string", example="1234"),
     *             @OA\Property(property="email", type="string", format="email", example="mamadou@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="nom", type="string"),
     *                     @OA\Property(property="prenom", type="string"),
     *                     @OA\Property(property="telephone", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 ),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        $user = $this->auth->register($request->validated());
        $token = $user->createToken('auth_token')->accessToken;

        return new AuthResource($user,'');
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur avec son téléphone et code PIN",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "code"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="code", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="nom", type="string"),
     *                     @OA\Property(property="prenom", type="string"),
     *                     @OA\Property(property="telephone", type="string")
     *                 ),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants incorrects",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=423,
     *         description="Compte bloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="account_blocked"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="attempts", type="integer"),
     *             @OA\Property(property="blocked_until", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $result = $this->auth->login(
            $validated['telephone'],
            $validated['code']
        );

        // Utilisateur non trouvé
        if (!$result) {
            return $this->unauthorizedResponse('Utilisateur non trouvé');
        }

        // Cas d'erreur (compte bloqué ou code incorrect)
        if (isset($result['error'])) {
            return match ($result['error']) {
                'account_blocked' => $this->accountBlockedResponse(
                    $result['message'] ?? 'Compte temporairement bloqué',
                    array_diff_key($result, ['error' => '', 'message' => ''])
                ),
                'invalid_credentials' => $this->unauthorizedResponse(
                    $result['message'] ?? 'Identifiants incorrects'
                ),
                default => $this->errorResponse($result['message'] ?? 'Erreur de connexion')
            };
        }

        // Connexion réussie
        return new AuthResource($result['user'], $result['access_token']);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     description="Révoque le token d'accès de l'utilisateur connecté",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Déconnecté avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            $passportService = app(\App\Services\MongoPassportService::class);
            $passportService->revokeToken($token);
        }

        return $this->successResponse(null, 'Déconnecté avec succès');
    }

    /**
     * @OA\Post(
     *     path="/auth/unlock-account",
     *     summary="Débloquer un compte utilisateur",
     *     description="Réinitialise les tentatives de connexion et débloque un compte (utilitaire admin/test)",
     *     operationId="unlockAccount",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function unlockAccount(Request $request)
    {
        $request->validate([
            'telephone' => ['required', 'string']
        ]);

        $user = User::where('telephone', $request->telephone)->first();

        if (!$user) {
            return $this->notFoundResponse('Utilisateur non trouvé');
        }

        $user->resetLoginAttempts();

        return $this->successResponse([
            'user_id' => $user->id
        ], 'Compte débloqué avec succès');
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Obtenir les informations de l'utilisateur connecté",
     *     description="Récupère les informations du profil de l'utilisateur authentifié",
     *     operationId="me",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur récupérées",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse('Utilisateur non authentifié');
        }

        return $this->successResponse($user, 'Informations utilisateur récupérées');
    }
}
