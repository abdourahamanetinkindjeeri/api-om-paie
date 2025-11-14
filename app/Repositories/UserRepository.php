<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Trouve un utilisateur par téléphone
     */
    public function findByTelephone(string $telephone): ?User
    {
        return Cache::remember("user_telephone_{$telephone}", 3600, function () use ($telephone) {
            return User::where('telephone', $telephone)->first();
        });
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function create(array $data): User
    {
        // S'assurer que l'ID est un UUID
        if (!isset($data['id'])) {
            $data['id'] = (string) Str::uuid();
        }

        return User::create($data);
    }

    /**
     * Vérifie si un utilisateur existe avec ce téléphone
     */
    public function existsByTelephone(string $telephone): bool
    {
        return User::where('telephone', $telephone)->exists();
    }

    /**
     * Vérifie si un utilisateur existe avec cet email
     */
    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Vérifie si un utilisateur existe avec ce numéro de pièce
     */
    public function existsByNumero(string $numero): bool
    {
        return User::where('numero', $numero)->exists();
    }

    /**
     * Trouve un utilisateur par email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
