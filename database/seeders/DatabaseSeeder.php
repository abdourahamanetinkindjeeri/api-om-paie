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

        $this->call([
            UserSeeder::class,
            MerchantSeeder::class,
            WalletSeeder::class,
            TransactionSeeder::class,
            TransactionLogSeeder::class,
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
