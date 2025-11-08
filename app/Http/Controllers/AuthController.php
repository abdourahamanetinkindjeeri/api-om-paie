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
     * Inscription
     */
    public function register(RegisterRequest $request)
    {
        $user = $this->auth->register($request->validated());
        $token = $user->createToken('auth_token')->accessToken;

        return new AuthResource($user, $token);
    }

    /**
     * Connexion
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
            return response()->json([
                'error' => 'not_found',
                'message' => 'Utilisateur non trouvé'
            ], 401);
        }

        // Cas d'erreur (compte bloqué ou code incorrect)
        if (isset($result['error'])) {
            $statusCode = match ($result['error']) {
                'account_blocked' => 423, // Locked
                'invalid_credentials' => 401, // Unauthorized
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        // Connexion réussie
        return new AuthResource($result['user'], $result['access_token']);
    }

    /**
     * Déconnexion
     */
    public function logout()
    {
        auth()->user()->token()->revoke();

        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    /**
     * Débloquer un compte (admin ou tests)
     */
    public function unlockAccount(Request $request)
    {
        $request->validate([
            'telephone' => ['required', 'string']
        ]);

        $user = User::where('telephone', $request->telephone)->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $user->resetLoginAttempts();

        return response()->json([
            'message' => 'Compte débloqué avec succès',
            'user_id' => $user->id
        ]);
    }
}
