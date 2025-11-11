<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateMissingWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:create-missing
                            {--dry-run : Afficher les utilisateurs sans wallet sans créer les wallets}
                            {--balance=0 : Solde initial pour les nouveaux wallets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer des wallets pour tous les utilisateurs qui n\'en ont pas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $initialBalance = (float) $this->option('balance');

        $this->info('🔍 Recherche des utilisateurs sans wallet...');

        // Récupérer tous les utilisateurs
        $users = User::all();
        $usersWithoutWallet = [];

        foreach ($users as $user) {
            $hasWallet = Wallet::where('user_id', $user->id)->exists();

            if (!$hasWallet) {
                $usersWithoutWallet[] = $user;
            }
        }

        $count = count($usersWithoutWallet);

        if ($count === 0) {
            $this->info('✅ Tous les utilisateurs ont déjà un wallet !');
            return 0;
        }

        $this->warn("⚠️  {$count} utilisateur(s) sans wallet trouvé(s)");

        if ($isDryRun) {
            $this->info('🔍 Mode DRY-RUN : Aucun wallet ne sera créé');
            $this->table(
                ['ID', 'Nom', 'Prénom', 'Téléphone', 'Email'],
                array_map(function ($user) {
                    return [
                        $user->id,
                        $user->nom,
                        $user->prenom,
                        $user->telephone ?? 'N/A',
                        $user->email ?? 'N/A',
                    ];
                }, $usersWithoutWallet)
            );
            return 0;
        }

        if (!$this->confirm("Voulez-vous créer {$count} wallet(s) avec un solde initial de {$initialBalance} XOF ?", true)) {
            $this->info('❌ Opération annulée');
            return 1;
        }

        $this->info('💰 Création des wallets...');
        $bar = $this->output->createProgressBar($count);

        $created = 0;
        $errors = 0;

        foreach ($usersWithoutWallet as $user) {
            try {
                Wallet::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'balance' => $initialBalance,
                    'currency' => 'XOF'
                ]);
                $created++;
            } catch (\Exception $e) {
                $this->error("\n❌ Erreur pour l'utilisateur {$user->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$created} wallet(s) créé(s) avec succès");

        if ($errors > 0) {
            $this->error("❌ {$errors} erreur(s) rencontrée(s)");
        }

        return 0;
    }
}
