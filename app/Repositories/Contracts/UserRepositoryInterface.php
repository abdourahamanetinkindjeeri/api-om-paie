<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Trouve un utilisateur par téléphone
     */
    public function findByTelephone(string $telephone): ?User;

    /**
     * Crée un nouvel utilisateur
     */
    public function create(array $data): User;

    /**
     * Vérifie si un utilisateur existe avec ce téléphone
     */
    public function existsByTelephone(string $telephone): bool;

    /**
     * Vérifie si un utilisateur existe avec cet email
     */
    public function existsByEmail(string $email): bool;

    /**
     * Vérifie si un utilisateur existe avec ce numéro de pièce
     */
    public function existsByNumero(string $numero): bool;

    /**
     * Trouve un utilisateur par email
     */
    public function findByEmail(string $email): ?User;
}
