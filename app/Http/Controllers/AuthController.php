<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function register(RegisterRequest $request)
    {
        $user = $this->auth->register($request->validated());
        $token = $user->createToken('auth_token')->accessToken;

        return new AuthResource($user, $token);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $tokenData = $this->auth->login(
            $validated['telephone'],
            $validated['code']
        );

        if (!$tokenData) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        // Ici $tokenData['access_token'] est ton token Passport
        return new AuthResource(auth()->user(), $tokenData['access_token']);
    }

    /**
     * Déconnexion (invalider le token Passport)
     */
    public function logout()
    {
        auth()->user()->token()->revoke();

        return response()->json(['message' => 'Déconnecté avec succès']);
    }
}
