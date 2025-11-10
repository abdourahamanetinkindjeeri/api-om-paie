<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SimpleAuthService
{
    public function __construct(private UserRepository $users) {}

    public function register(array $data): User
    {
        return $this->users->create($data);
    }

    public function login(string $telephone, string $password): ?array
    {
        $user = $this->users->findByTelephone($telephone);

        if (!$user) {
            return null;
        }

        // Vérifier si le compte est bloqué
        if ($user->isBlocked()) {
            return $this->blockedResponse($user, 'Compte bloqué temporairement');
        }

        // Vérifier le code PIN
        if (!Hash::check($password, $user->code)) {
            $user->incrementLoginAttempts();

            if ($user->isBlocked()) {
                return $this->blockedResponse($user, 'Compte bloqué après ' . User::MAX_LOGIN_ATTEMPTS . ' tentatives incorrectes');
            }

            return [
                'error' => 'invalid_credentials',
                'message' => 'Code incorrect',
                'attempts_remaining' => $user->remainingAttempts(),
            ];
        }

        // Connexion réussie
        $user->resetLoginAttempts();

        // Générer un token simple au lieu d'utiliser Passport
        $token = base64_encode(json_encode([
            'user_id' => $user->id,
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addHours(24)->timestamp,
            'random' => Str::random(32)
        ]));

        return [
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_at'   => now()->addHours(24),
        ];
    }

    private function blockedResponse(User $user, string $message): array
    {
        return [
            'error'             => 'account_blocked',
            'message'           => $message,
            'blocked_until'     => $user->blocked_until,
            'remaining_minutes' => $user->getBlockedTimeRemaining(),
        ];
    }
}
