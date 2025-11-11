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

    /**
     * Le nombre de tentatives du job.
     */
    public int $tries = 3;

    /**
     * Le délai d'expiration du job en secondes.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $userId,
        public string $identifier
    ) {
        // Définir la queue spécifique pour les notifications
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(OtpServiceInterface $otpService): void
    {
        try {
            // Vérifier que l'utilisateur existe toujours
            $user = User::find($this->userId);
            if (!$user) {
                Log::warning('Utilisateur introuvable pour l\'envoi d\'OTP de bienvenue', [
                    'user_id' => $this->userId
                ]);
                return;
            }

            // Envoyer l'OTP via le service
            $success = $otpService->generateAndSend($this->identifier, 'registration');

            if ($success) {
                Log::info('OTP de bienvenue envoyé avec succès (asynchrone)', [
                    'user_id' => $this->userId,
                    'identifier' => $this->identifier,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                Log::error('Échec de l\'envoi d\'OTP de bienvenue (asynchrone)', [
                    'user_id' => $this->userId,
                    'identifier' => $this->identifier,
                    'job_id' => $this->job->getJobId()
                ]);

                // Relancer le job si c'est le premier échec
                if ($this->attempts() < $this->tries) {
                    $this->release(30); // Réessayer dans 30 secondes
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi d\'OTP de bienvenue (asynchrone)', [
                'user_id' => $this->userId,
                'identifier' => $this->identifier,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId()
            ]);

            // Relancer le job en cas d'erreur
            if ($this->attempts() < $this->tries) {
                $this->release(60); // Réessayer dans 60 secondes
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Gestion des échecs du job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job d\'envoi d\'OTP de bienvenue échoué définitivement', [
            'user_id' => $this->userId,
            'identifier' => $this->identifier,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
