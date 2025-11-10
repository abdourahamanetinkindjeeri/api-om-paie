<?php

namespace App\Services\Contracts;

interface RegistrationServiceInterface
{
    /**
     * Initie le processus d'enregistrement d'un utilisateur
     */
    public function initiateRegistration(array $userData): array;

    /**
     * Confirme l'enregistrement avec le code OTP
     */
    public function confirmRegistration(string $identifier, string $otpCode, array $userData = []): array;
}
