<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Services\Contracts\OtpServiceInterface;
use App\Facades\Notification;
use Illuminate\Support\Facades\Log;

class OtpService implements OtpServiceInterface
{
    /**
     * Génère et envoie un code OTP
     */
    public function generateAndSend(string $identifier, string $purpose = 'registration'): bool
    {
        try {
            // Générer le code OTP
            $code = OtpCode::generate($identifier, $purpose);

            // Préparer le message
            $message = $this->buildMessage($code, $purpose);

            // Utiliser l'email de test pour les numéros de téléphone en développement
            $destination = $this->getDestination($identifier);

            // Envoyer via la facade Notification
            Notification::send($destination, $message);

            Log::info("Code OTP envoyé", [
                'identifier' => $identifier,
                'destination' => $destination,
                'purpose' => $purpose
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du code OTP", [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Vérifie un code OTP
     */
    public function verify(string $identifier, string $code, string $purpose = 'registration'): bool
    {
        $isValid = OtpCode::verify($identifier, $code, $purpose);

        Log::info("Vérification code OTP", [
            'identifier' => $identifier,
            'purpose' => $purpose,
            'valid' => $isValid
        ]);

        return $isValid;
    }

    /**
     * Nettoie les anciens codes OTP
     */
    public function cleanup(): int
    {
        $deleted = OtpCode::cleanup();

        Log::info("Nettoyage des codes OTP", [
            'deleted_count' => $deleted
        ]);

        return $deleted;
    }

    /**
     * Détermine la destination de l'envoi (email de test pour les téléphones en dev)
     */
    private function getDestination(string $identifier): string
    {
        // Si c'est un numéro de téléphone et qu'on est en développement, utiliser l'email de test
        if (preg_match('/^\+221[0-9]{9}$/', $identifier) && config('app.env') !== 'production') {
            return 'jeeridev@gmail.com';
        }

        return $identifier;
    }

    /**
     * Construit le message à envoyer
     */
    private function buildMessage(string $code, string $purpose): string
    {
        $messages = [
            'registration' => "Votre code de vérification OM-Paie est: {$code}. Ce code à 6 chiffres expire dans 5 minutes.",
            'password_reset' => "Votre code de réinitialisation OM-Paie est: {$code}. Ce code à 6 chiffres expire dans 5 minutes.",
            'login' => "Votre code de connexion OM-Paie est: {$code}. Ce code à 6 chiffres expire dans 5 minutes.",
        ];

        return $messages[$purpose] ?? "Votre code OM-Paie est: {$code}. Ce code à 6 chiffres expire dans 5 minutes.";
    }
}
