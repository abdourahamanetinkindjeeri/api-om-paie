<?php

namespace App\Services\Contracts;

interface OtpServiceInterface
{
    /**
     * Génère et envoie un code OTP
     */
    public function generateAndSend(string $identifier, string $purpose = 'registration'): bool;

    /**
     * Vérifie un code OTP
     */
    public function verify(string $identifier, string $code, string $purpose = 'registration'): bool;

    /**
     * Nettoie les anciens codes OTP
     */
    public function cleanup(): int;
}
