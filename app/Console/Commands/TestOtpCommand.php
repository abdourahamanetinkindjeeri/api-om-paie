<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestOtpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:test {identifier} {--purpose=registration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester l\'envoi d\'un code OTP';

    /**
     * Execute the console command.
     */
    public function handle(OtpServiceInterface $otpService)
    {
        $identifier = $this->argument('identifier');
        $purpose = $this->option('purpose');

        $this->info("🧪 Test d'envoi d'OTP...");
        $this->info("📧 Destinataire : {$identifier}");
        $this->info("🎯 Objectif : {$purpose}");
        $this->newLine();

        // Afficher la configuration mail
        $this->displayMailConfig();

        // Tester l'envoi
        try {
            $this->info("📨 Envoi du code OTP en cours...");

            $success = $otpService->generateAndSend($identifier, $purpose);

            if ($success) {
                $this->info("✅ OTP envoyé avec succès !");

                // Récupérer le code depuis la base de données pour affichage
                $otp = \App\Models\OtpCode::where('identifier', $identifier)
                    ->where('purpose', $purpose)
                    ->where('expires_at', '>', now())
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($otp) {
                    $this->info("🔐 Code généré : {$otp->code}");
                    $this->info("⏰ Expire à : {$otp->expires_at}");
                }
            } else {
                $this->error("❌ Échec de l'envoi de l'OTP");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
            $this->error("Stack trace : " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Afficher la configuration mail
     */
    private function displayMailConfig(): void
    {
        $this->info("📋 Configuration mail :");

        $config = [
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'),
            'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username') ? '***configuré***' : '❌ NON CONFIGURÉ',
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
            'APP_ENV' => config('app.env'),
        ];

        foreach ($config as $key => $value) {
            $status = (empty($value) || $value === '❌ NON CONFIGURÉ') ? '❌' : '✅';
            $this->line("  {$status} {$key}: {$value}");
        }

        $this->newLine();
    }
}
