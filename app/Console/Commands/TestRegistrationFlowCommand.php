<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\SendWelcomeOtpJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestRegistrationFlowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:registration {email} {--telephone=+221771234567}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester le flux complet d\'inscription avec envoi d\'OTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $telephone = $this->option('telephone');

        $this->info("🧪 Test du flux d'inscription...");
        $this->newLine();

        try {
            // Créer un utilisateur de test
            $this->info("1️⃣ Création de l'utilisateur de test...");

            $user = User::create([
                'nom' => 'Test',
                'prenom' => 'Utilisateur',
                'telephone' => $telephone,
                'email' => $email,
                'type_piece' => 'cin',
                'numero' => 'TEST' . rand(1000000000, 9999999999),
                'adresse' => 'Adresse de test',
                'code' => bcrypt('1234'),
            ]);

            $this->info("✅ Utilisateur créé : {$user->id}");
            $this->newLine();

            // Dispatcher le job
            $this->info("2️⃣ Envoi du job SendWelcomeOtpJob...");
            $identifier = $user->email ?: $user->telephone;

            SendWelcomeOtpJob::dispatch($user->id, $identifier);

            $this->info("✅ Job dispatché avec succès");
            $this->info("📧 Identifier : {$identifier}");
            $this->newLine();

            // Attendre un peu pour que le job s'exécute (mode sync)
            sleep(2);

            // Vérifier si un OTP a été créé
            $this->info("3️⃣ Vérification de la création de l'OTP...");

            $otp = DB::table('otp_codes')
                ->where('identifier', $identifier)
                ->where('purpose', 'registration')
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->first();

            if ($otp) {
                $this->info("✅ OTP créé avec succès !");
                $this->info("🔐 Code : {$otp->code}");
                $this->info("⏰ Expire à : {$otp->expires_at}");
            } else {
                $this->warn("⚠️ Aucun OTP trouvé - vérifiez les logs");
            }

            $this->newLine();
            $this->info("4️⃣ Vérification des logs...");
            $this->line("Consultez les logs pour voir si l'email a été envoyé :");
            $this->line("tail -n 50 storage/logs/laravel.log | grep -i 'otp\\|email'");

            $this->newLine();
            $this->info("✅ Test terminé !");
            $this->newLine();

            $this->info("📝 Pour nettoyer :");
            $this->line("User ID à supprimer : {$user->id}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
            $this->error("Stack trace : ");
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
