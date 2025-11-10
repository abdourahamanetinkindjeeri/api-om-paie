<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Démarrage du seeding de la base de données...');
        $this->command->newLine();

        // Seeders de production avec noms africains
        $this->call([
            UserSeeder::class,           // Utilisateurs principaux
            MerchantSeeder::class,       // Marchands avec noms africains
            WalletSeeder::class,         // Portefeuilles des utilisateurs et marchands
            // MongoDBSeeder::class,     // Alternative MongoDB (optionnel)
        ]);

        // Seeders de développement activés
        $this->call([
            TransactionSeeder::class,     // Transactions de test
            TransactionLogSeeder::class,  // Logs de transactions
            // MerchantTestSeeder::class,    // Marchands de test supplémentaires (désactivé - pas nécessaire)
            // TransferTestSeeder::class,    // Utilisateurs pour test de transfert (désactivé - pas nécessaire)
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Base de données seedée avec succès !');
        $this->command->info('📊 Résumé :');
        $this->command->table(
            ['Modèle', 'Nombre'],
            [
                ['Users', User::count()],
                ['Merchants', Merchant::count()],
                ['Wallets', Wallet::count()],
                ['Transactions', Transaction::count()],
                ['Transaction Logs', TransactionLog::count()],
            ]
        );
    }
}
