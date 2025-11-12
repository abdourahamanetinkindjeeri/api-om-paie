<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function register(array $data): User
    {
        // Pas besoin de Hash::make ici, le mutateur User::setCodeAttribute s’en charge
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

        $tokenResult = $user->createToken('auth_token');

        return [
            'user'                 => $user,
            'access_token'         => $tokenResult->accessToken,
            'token_type'           => 'Bearer',
            'expires_at'           => $tokenResult->token->expires_at,
            'expires_in'           => 15 * 60, // 15 minutes
            'refresh_token'        => $tokenResult->refreshToken ?? null,
            'refresh_expires_at'   => $tokenResult->refresh_expires_at ?? null,
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
