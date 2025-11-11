<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestMailProductionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-production {email} {--otp=123456}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester l\'envoi d\'emails en production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $otpCode = $this->option('otp');

        $this->info("🧪 Test de l'envoi d'email en production...");

        // Vérifier la configuration mail
        $this->checkMailConfiguration();

        // Envoyer l'email de test
        try {
            $emailBody = $this->buildTestEmailBody($otpCode);

            Mail::raw($emailBody, function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email - OM Paie Production')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info("✅ Email envoyé avec succès à : {$email}");
            $this->info("🔐 Code OTP utilisé : {$otpCode}");

            Log::info("Email de test production envoyé", [
                'email' => $email,
                'otp' => $otpCode,
                'environment' => app()->environment()
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'envoi : " . $e->getMessage());

            Log::error("Erreur envoi email test production", [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Vérifier la configuration mail
     */
    private function checkMailConfiguration(): void
    {
        $this->info("📋 Configuration mail actuelle :");

        $config = [
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'),
            'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username') ? '***configuré***' : '❌ NON CONFIGURÉ',
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
            'QUEUE_CONNECTION' => config('queue.default'),
        ];

        foreach ($config as $key => $value) {
            $status = (empty($value) || $value === '❌ NON CONFIGURÉ') ? '❌' : '✅';
            $this->line("  {$status} {$key}: {$value}");
        }

        $this->newLine();
    }

    /**
     * Construire le corps de l'email de test
     */
    private function buildTestEmailBody(string $otpCode): string
    {
        $environment = app()->environment();
        $timestamp = now()->format('Y-m-d H:i:s');

        return "
🧪 EMAIL DE TEST - OM PAIE PRODUCTION

Ceci est un email de test envoyé depuis l'environnement : {$environment}
Timestamp : {$timestamp}

Code OTP de test : {$otpCode}

Configuration utilisée :
- Mailer : " . config('mail.default') . "
- Host : " . config('mail.mailers.smtp.host') . "
- Port : " . config('mail.mailers.smtp.port') . "
- Encryption : " . config('mail.mailers.smtp.encryption') . "
- From : " . config('mail.from.address') . "

Si vous recevez cet email, la configuration mail fonctionne correctement ! ✅

---
OM Paie - Solution de gestion de paie
Test automatique - Ne pas répondre à cet email
        ";
    }
}
