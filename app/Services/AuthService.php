<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function register(array $data): User
    {
        // Hash automatique du champ "code"
        $data['code'] = Hash::make($data['code']);
        return $this->users->create($data);
    }

    public function login(string $telephone, string $password): ?array
    {
        $user = $this->users->findByTelephone($telephone);

        if (!$user || !Hash::check($password, $user->code)) {
            return null;
        }

        // Création du token Passport
        $tokenResult = $user->createToken('auth_token');
        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $tokenResult->token->expires_at,
        ];
    }
}
