<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CheckMailConfigCommand extends Command
{
    protected $signature = 'mail:check-config';
    protected $description = 'Vérifier la configuration mail et tester l\'envoi';

    public function handle()
    {
        $this->info("🔍 Vérification de la configuration mail...");
        $this->newLine();

        // Afficher la configuration
        $this->displayConfig();

        // Vérifier les valeurs critiques
        $this->checkCriticalValues();

        return Command::SUCCESS;
    }

    private function displayConfig(): void
    {
        $this->info("📋 Configuration actuelle :");

        $config = [
            'APP_ENV' => config('app.env'),
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'),
            'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username') ? '✅ Configuré' : '❌ NON CONFIGURÉ',
            'MAIL_PASSWORD' => config('mail.mailers.smtp.password') ? '✅ Configuré' : '❌ NON CONFIGURÉ',
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
        ];

        foreach ($config as $key => $value) {
            $this->line("  {$key}: {$value}");
        }

        $this->newLine();
    }

    private function checkCriticalValues(): void
    {
        $this->info("🔬 Vérification des valeurs critiques :");

        $errors = [];
        $warnings = [];

        // Vérifier MAIL_USERNAME
        if (empty(config('mail.mailers.smtp.username'))) {
            $errors[] = 'MAIL_USERNAME n\'est pas configuré';
        }

        // Vérifier MAIL_PASSWORD
        if (empty(config('mail.mailers.smtp.password'))) {
            $errors[] = 'MAIL_PASSWORD n\'est pas configuré';
        }

        // Vérifier MAIL_FROM_ADDRESS
        if (empty(config('mail.from.address'))) {
            $errors[] = 'MAIL_FROM_ADDRESS n\'est pas configuré';
        }

        // Vérifier si en production avec mail.log
        if (config('app.env') === 'production' && config('mail.default') === 'log') {
            $warnings[] = 'En production avec driver "log" - les emails ne seront pas envoyés !';
        }

        // Afficher les erreurs
        if (count($errors) > 0) {
            $this->error("❌ Erreurs détectées :");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
            $this->newLine();

            $this->warn("Pour configurer sur Render :");
            $this->line("1. Allez sur dashboard.render.com");
            $this->line("2. Sélectionnez votre service");
            $this->line("3. Allez dans Environment");
            $this->line("4. Ajoutez les variables MAIL_* manquantes");
            $this->newLine();
        }

        // Afficher les warnings
        if (count($warnings) > 0) {
            $this->warn("⚠️ Avertissements :");
            foreach ($warnings as $warning) {
                $this->line("  - {$warning}");
            }
            $this->newLine();
        }

        // Tout est OK
        if (count($errors) === 0 && count($warnings) === 0) {
            $this->info("✅ Configuration mail complète !");
            $this->newLine();

            $this->info("💡 Pour tester l'envoi d'email :");
            $this->line("  php artisan mail:test-production votre@email.com");
        }
    }
}
