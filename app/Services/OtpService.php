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
    public function generateAndSend(string $identifier, string $purpose = 'registration'): array
    {
        try {
            // Générer le code OTP
            $code = OtpCode::generate($identifier, $purpose);

            // Préparer le message
            $message = $this->buildMessage($code, $purpose);

            // Déterminer si c'est un numéro de téléphone ou un email
            $isPhone = preg_match('/^\+[1-9]\d{1,14}$/', $identifier);

            if ($isPhone) {
                // Pour les téléphones, toujours envoyer par SMS via Twilio (synchrone pour OTP)
                Notification::sendSync($identifier, $message);

                Log::info("Code OTP envoyé par SMS", [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                    'environment' => config('app.env')
                ]);
            } else {
                // Pour les emails, envoyer directement (synchrone pour OTP)
                Notification::sendSync($identifier, $message);

                Log::info("Code OTP envoyé par email", [
                    'identifier' => $identifier,
                    'purpose' => $purpose
                ]);
            }

            if (app()->environment(['local','testing'])) {
                Log::debug('OTP généré', [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                    'code' => $code
                ]);
            }

            return [
                'success' => true,
                'code' => $code,
                'expires_in_minutes' => OtpCode::DEFAULT_EXPIRY_MINUTES
            ];
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du code OTP", [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
     * Construit le message à envoyer
     */
    private function buildMessage(string $code, string $purpose): string
    {
        $messages = config('ompaie.otp.messages', []);
        $template = $messages[$purpose] ?? "Votre code OM-Paie est: {code}. Ce code à 6 chiffres expire dans 5 minutes.";

        return str_replace('{code}', $code, $template);
    }
}
