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

            // Déterminer la destination email
            $emailDestination = $this->getEmailDestination($user);

            if (!$emailDestination) {
                Log::warning('Pas d\'email valide pour OTP', [
                    'user_id' => $this->userId,
                    'identifier' => $this->identifier
                ]);
                return;
            }

            // Envoyer l'OTP directement à l'email
            $success = $otpService->generateAndSend($emailDestination, 'registration');

            if ($success) {
                Log::info('OTP bienvenue envoyé', [
                    'user_id' => $this->userId,
                    'email' => $emailDestination
                ]);
            } else {
                Log::error('Échec envoi OTP bienvenue', [
                    'user_id' => $this->userId
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release(30);
                }
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

        // En dev/local, utiliser l'email de test
        if (config('app.env') !== 'production') {
            return 'jeeridev@gmail.com';
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
