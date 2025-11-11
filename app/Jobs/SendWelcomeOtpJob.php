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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SendWelcomeOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Le nombre de tentatives du job.
     */
    public $tries = 3;

    /**
     * Le délai d'expiration du job en secondes.
     */
    public $timeout = 30;

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
            $user = User::find($this->userId);

            if (!$user) {
                Log::warning('Utilisateur introuvable pour l\'envoi d\'OTP de bienvenue', [
                    'user_id' => $this->userId
                ]);
                return;
            }

            $success = $otpService->generateAndSend($this->identifier, 'registration');

            if ($success && filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
                $this->sendOtpByEmail($this->identifier, $user);
            }

            if ($success) {
                Log::info('OTP de bienvenue envoyé avec succès (asynchrone)', [
                    'user_id' => $this->userId,
                    'identifier' => $this->identifier,
                ]);
            } else {
                Log::error('Échec de l\'envoi d\'OTP de bienvenue (asynchrone)', [
                    'user_id' => $this->userId,
                    'identifier' => $this->identifier,
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release(30);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi d\'OTP de bienvenue (asynchrone)', [
                'user_id' => $this->userId,
                'identifier' => $this->identifier,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release(60);
            } else {
                $this->fail($e);
            }
        }
    }

    private function sendOtpByEmail(string $email, User $user): void
    {
        try {
            $otpCode = $this->getOtpCode($email);

            if (!$otpCode) {
                Log::warning('Code OTP non trouvé pour l\'envoi par email', [
                    'email' => $email,
                    'user_id' => $this->userId
                ]);
                return;
            }

            $emailBody = $this->buildOtpEmailBody($otpCode, $user->name ?? 'Utilisateur');

            Mail::raw($emailBody, function ($message) use ($email) {
                $message->to($email)
                    ->subject('Code OTP - OM Paie')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Email OTP envoyé avec succès', [
                'email' => $email,
                'user_id' => $this->userId
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email OTP', [
                'email' => $email,
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getOtpCode(string $identifier): ?string
    {
        try {
            $cacheKey = "otp_registration_{$identifier}";
            $otpData = Cache::get($cacheKey);

            if ($otpData && is_array($otpData)) {
                return $otpData['code'] ?? null;
            }

            $otpRecord = DB::table('otp_codes')
                ->where('identifier', $identifier)
                ->where('type', 'registration')
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->first();

            return $otpRecord ? (string) $otpRecord->code : null;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du code OTP', [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function buildOtpEmailBody(string $otpCode, string $userName): string
    {
        return "
Bonjour {$userName},

Bienvenue sur OM Paie !

Voici votre code de vérification OTP pour finaliser votre inscription :

CODE OTP : {$otpCode}

Instructions :
- Saisissez ce code dans l'application OM Paie
- Ce code est valide pendant 10 minutes
- Ne partagez jamais ce code avec d'autres personnes

⚠️ SÉCURITÉ :
Si vous n'avez pas demandé ce code, ignorez cet email et contactez notre support.

Cordialement,
L'équipe OM Paie

---
OM Paie - Solution de gestion de paie
Cet email a été envoyé automatiquement, merci de ne pas y répondre.
        ";
    }

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
