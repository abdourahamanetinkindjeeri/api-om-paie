<?php

namespace App\Jobs;

use App\Services\Contracts\OtpServiceInterface;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWelcomeOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        public string $userId,
        public string $identifier
    ) {
        $this->onQueue('notifications');
    }

    public function handle(OtpServiceInterface $otpService): void
    {
        try {
            $user = User::find($this->userId);

            if (!$user) {
                Log::warning('Utilisateur introuvable pour OTP bienvenue', [
                    'user_id' => $this->userId
                ]);
                return;
            }

            $emailSuccess = false;
            $smsSuccess = false;

            // Envoyer par email si disponible
            $emailDestination = $this->getEmailDestination($user);
            if ($emailDestination) {
                $emailSuccess = $otpService->generateAndSend($emailDestination, 'registration');

                if ($emailSuccess) {
                    Log::info('OTP bienvenue envoyé par email', [
                        'user_id' => $this->userId,
                        'email' => $emailDestination
                    ]);
                }
            }

            // Envoyer par SMS si téléphone disponible
            $phoneDestination = $this->getPhoneDestination($user);
            if ($phoneDestination) {
                $smsSuccess = $otpService->generateAndSend($phoneDestination, 'registration');

                if ($smsSuccess) {
                    Log::info('OTP bienvenue envoyé par SMS', [
                        'user_id' => $this->userId,
                        'telephone' => $phoneDestination
                    ]);
                }
            }

            // Si aucun envoi n'a réussi
            if (!$emailSuccess && !$smsSuccess) {
                Log::error('Échec envoi OTP bienvenue sur tous les canaux', [
                    'user_id' => $this->userId,
                    'email_tried' => !empty($emailDestination),
                    'sms_tried' => !empty($phoneDestination)
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release(30);
                }
            } else {
                Log::info('OTP bienvenue - Résumé envoi', [
                    'user_id' => $this->userId,
                    'email_success' => $emailSuccess,
                    'sms_success' => $smsSuccess
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur job OTP bienvenue', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release(60);
            } else {
                $this->fail($e);
            }
        }
    }

    private function getEmailDestination(User $user): ?string
    {
        // Si l'identifier est un email, l'utiliser
        if (filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->identifier;
        }

        // Sinon utiliser l'email du user
        if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return $user->email;
        }

        return null;
    }

    private function getPhoneDestination(User $user): ?string
    {
        // Si l'identifier est un téléphone, l'utiliser
        if (preg_match('/^\+[1-9]\d{1,14}$/', $this->identifier)) {
            return $this->identifier;
        }

        // Sinon utiliser le téléphone du user
        if ($user->telephone && preg_match('/^\+[1-9]\d{1,14}$/', $user->telephone)) {
            return $user->telephone;
        }

        return null;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job OTP bienvenue échoué définitivement', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);
    }
}
