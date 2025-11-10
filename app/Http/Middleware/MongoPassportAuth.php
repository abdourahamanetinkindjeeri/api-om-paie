<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MongoPassportService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;

class MongoPassportAuth
{
    protected $passportService;

    public function __construct(MongoPassportService $passportService)
    {
        $this->passportService = $passportService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token manquant'], 401);
        }

        $tokenRecord = $this->passportService->validateToken($token);

        if (!$tokenRecord) {
            return response()->json(['message' => 'Token invalide ou expiré'], 401);
        }

        // Récupérer l'utilisateur associé au token
        $user = User::find($tokenRecord->user_id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 401);
        }

        // Définir l'utilisateur authentifié dans Laravel Auth
        auth()->setUser($user);

        return $next($request);
    }
}
