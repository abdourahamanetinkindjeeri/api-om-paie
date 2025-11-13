<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Services\Contracts\OtpServiceInterface;
use App\Services\RateLimiter;
use App\Facades\Notification;
use Illuminate\Support\Facades\Log;

class OtpService implements OtpServiceInterface
{
    protected RateLimiter $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Génère et envoie un code OTP
     */
    public function generateAndSend(string $identifier, string $purpose = 'registration'): array
    {
        try {
            // Vérifier les limites de taux pour la génération d'OTP
            $rateLimitKey = "otp:{$identifier}";
            $rateCheck = $this->rateLimiter->attempt($rateLimitKey, 'otp_generation');

            if (!$rateCheck['allowed']) {
                Log::warning("Rate limit exceeded for OTP generation", [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                    'blocked_until' => $rateCheck['blocked_until']
                ]);

                return [
                    'success' => false,
                    'error' => 'Trop de tentatives. Veuillez réessayer plus tard.',
                    'blocked_until' => $rateCheck['blocked_until'],
                    'remaining_attempts' => $rateCheck['remaining_attempts']
                ];
            }

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
        // Vérifier les limites de taux pour la vérification d'OTP
        $rateLimitKey = "otp_verify:{$identifier}";
        $rateCheck = $this->rateLimiter->check($rateLimitKey, 'otp_verification');

        if (!$rateCheck['allowed']) {
            Log::warning("Rate limit exceeded for OTP verification", [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'blocked_until' => $rateCheck['blocked_until']
            ]);
            return false;
        }

        $isValid = OtpCode::verify($identifier, $code, $purpose);

        // Enregistrer la tentative seulement si elle échoue
        if (!$isValid) {
            $this->rateLimiter->recordAttempt($rateLimitKey, 'otp_verification');
        } else {
            // Réinitialiser le compteur en cas de succès
            $this->rateLimiter->reset($rateLimitKey, 'otp_verification');
        }

        Log::info("Vérification code OTP", [
            'identifier' => $identifier,
            'purpose' => $purpose,
            'valid' => $isValid,
            'remaining_attempts' => $rateCheck['remaining_attempts']
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
