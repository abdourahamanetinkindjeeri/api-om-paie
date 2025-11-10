<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Str;

class UserRepository
{
    /**
     * Trouve un utilisateur par téléphone
     */
    public function findByTelephone(string $telephone): ?User
    {
        return User::where('telephone', $telephone)->first();
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
