<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ConfirmRegistrationRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Resources\AuthResource;
use App\Services\AuthService;
use App\Services\Contracts\RegistrationServiceInterface;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private RegistrationServiceInterface $registrationService
    ) {}

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Initier l'inscription d'un nouvel utilisateur avec OTP",
     *     description="Démarre le processus d'inscription en envoyant un code OTP par email ou SMS. Au moins l'un des deux (téléphone ou email) est requis. L'utilisateur devra confirmer le code pour finaliser l'inscription et créer son compte avec wallet.",
     *     operationId="register",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="telephone", type="string", example="+221771234567", description="Numéro de téléphone sénégalais (+221 suivi de 9 chiffres). Optionnel si email fourni"),
     *             @OA\Property(property="email", type="string", format="email", example="dev.testghost@gmail.com", description="Adresse email. Optionnel si téléphone fourni"),
     *             @OA\Property(property="nom", type="string", maxLength=255, example="Diop", description="Nom de famille (optionnel)"),
     *             @OA\Property(property="prenom", type="string", maxLength=255, example="Mamadou", description="Prénom (optionnel)"),
     *             @OA\Property(property="type_piece", type="string", enum={"cin", "passport"}, example="cin", description="Type de pièce d'identité (optionnel)"),
     *             @OA\Property(property="numero", type="string", example="A1234567890123", description="Numéro de pièce d'identité (optionnel)"),
     *             @OA\Property(property="adresse", type="string", maxLength=500, example="Dakar, Sénégal", description="Adresse complète (optionnel)"),
     *             @OA\Property(property="code", type="string", example="1234", description="Code PIN choisi par l'utilisateur (optionnel à ce stade)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP envoyé avec succès - L'utilisateur doit confirmer pour finaliser l'inscription",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="identifier", type="string", example="+221771234567"),
     *                 @OA\Property(property="expires_in_minutes", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation des données"),
     *             @OA\Property(property="error_code", type="string", example="validation_error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="telephone", type="array", @OA\Items(type="string", example="Le téléphone doit être un numéro sénégalais valide (+221XXXXXXXXX).")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Un compte existe déjà avec cet email")),
     *                 @OA\Property(property="type_piece", type="array", @OA\Items(type="string", example="The selected type piece is invalid."))
     *             )
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->registrationService->initiateRegistration($request->validated());

            return $this->successResponse([
                'identifier' => $result['identifier'],
                'expires_in_minutes' => $result['expires_in_minutes']
            ], $result['message']);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'initiation de l\'inscription', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
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
        return $this->successResponse([
            'user' => $result['user'],
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'] ?? null,
            'expires_in' => $result['expires_in'] ?? (15 * 60),
            'refresh_token' => $result['refresh_token'] ?? null,
            'refresh_expires_at' => $result['refresh_expires_at'] ?? null,
        ], 'Connexion réussie');
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
     *     path="/auth/refresh",
     *     summary="Rafraîchir le jeton d'accès",
     *     description="Retourne un nouveau access_token (15 minutes) et un refresh_token rotaté (30 jours).",
     *     operationId="refreshToken",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="eyJ..."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time"),
     *                 @OA\Property(property="expires_in", type="integer", example=900),
     *                 @OA\Property(property="refresh_token", type="string"),
     *                 @OA\Property(property="refresh_expires_at", type="string", format="date-time"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token invalide ou expiré",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $passportService = app(\App\Services\MongoPassportService::class);
        $refreshed = $passportService->refreshAccessToken($request->input('refresh_token'));

        if (!$refreshed) {
            return $this->unauthorizedResponse('Refresh token invalide ou expiré');
        }

        return $this->successResponse([
            'user' => $refreshed['user'],
            'access_token' => $refreshed['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $refreshed['expires_at'],
            'expires_in' => $refreshed['expires_in'],
            'refresh_token' => $refreshed['refresh_token'],
            'refresh_expires_at' => $refreshed['refresh_expires_at'],
        ], 'Token rafraîchi');
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


    /**
     * @OA\Post(
     *     path="/auth/confirmation",
     *     summary="Confirmer l'inscription avec le code OTP",
     *     description="Valide le code OTP et crée le compte utilisateur avec son wallet",
     *     operationId="confirmRegistration",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "code_otp"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567", description="Téléphone utilisé pour l'inscription"),
     *             @OA\Property(property="code_otp", type="string", example="123456", description="Code OTP à 6 chiffres"),
     *             @OA\Property(property="nom", type="string", example="Diop", description="Nom (optionnel si déjà fourni)"),
     *             @OA\Property(property="prenom", type="string", example="Mamadou", description="Prénom (optionnel si déjà fourni)"),
     *             @OA\Property(property="type_piece", type="string", enum={"cin", "passport"}, example="cin", description="Type de pièce (optionnel)"),
     *             @OA\Property(property="numero", type="string", example="A1234567890123", description="Numéro de pièce (optionnel)"),
     *             @OA\Property(property="adresse", type="string", example="Dakar", description="Adresse (optionnel)"),
     *             @OA\Property(property="code", type="string", example="1234", description="Code PIN (optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", example="507f1f77bcf86cd799439011"),
     *                     @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="nom", type="string", example="Diop"),
     *                     @OA\Property(property="prenom", type="string", example="Mamadou"),
     *                     @OA\Property(property="wallet_id", type="string", example="507f1f77bcf86cd799439012")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou session expirée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code OTP invalide ou expiré")
     *         )
     *     ),
     *     @OA\Response(
     *         response=410,
     *         description="Session d'enregistrement expirée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données d'enregistrement expirées. Veuillez recommencer.")
     *         )
     *     )
     * )
     */
    public function confirm(ConfirmRegistrationRequest $request)
    {
        try {
            $validated = $request->validated();

            $result = $this->registrationService->confirmRegistration(
                $validated['identifier'],
                $validated['otp_code'],
                array_diff_key($validated, ['identifier' => '', 'otp_code' => ''])
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['user']
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la confirmation d\'inscription', [
                'error' => $e->getMessage()
            ]);

            $statusCode = str_contains($e->getMessage(), 'expirée') ? 410 : 400;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/resend",
     *     summary="Renvoyer le code OTP d'inscription",
     *     description="Renvoie un nouveau code OTP si le précédent a expiré ou n'a pas été reçu",
     *     operationId="resendOtp",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"identifier"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567", description="Téléphone utilisé pour l'inscription")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP renvoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP renvoyé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="identifier", type="string", example="+221771234567"),
     *                 @OA\Property(property="expires_in_minutes", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=410,
     *         description="Session d'enregistrement expirée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session d'enregistrement expirée. Veuillez recommencer le processus.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors du renvoi du code OTP")
     *         )
     *     )
     * )
     */
    public function resendOtp(ResendOtpRequest $request)
    {
        try {
            $result = $this->registrationService->resendOtp($request->validated()['identifier']);

            return $this->successResponse([
                'identifier' => $result['identifier'],
                'expires_in_minutes' => $result['expires_in_minutes']
            ], $result['message']);
        } catch (\Exception $e) {
            Log::error('Erreur lors du renvoi du code OTP', [
                'identifier' => $request->validated()['identifier'],
                'error' => $e->getMessage()
            ]);

            $statusCode = str_contains($e->getMessage(), 'expirée') ? 410 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
}
