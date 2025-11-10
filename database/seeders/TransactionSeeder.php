<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Wallet, Transaction, User};

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Utiliser les wallets existants créés par WalletSeeder (pas besoin d'en créer de nouveaux)
        $wallets = Wallet::take(5)->get(); // Prendre seulement 5 wallets

        foreach ($wallets as $wallet) {
            // Créer quelques transactions de test pour MongoDB
            $this->createTestTransactions($wallet);
        }

        $transactionCount = Transaction::count();
        $successCount = Transaction::where('status', 'success')->count();
        $pendingCount = Transaction::where('status', 'pending')->count();
        $failedCount = Transaction::where('status', 'failed')->count();

        $this->command->info('✅ ' . $transactionCount . ' transactions créées avec succès');
        $this->command->info('   - ' . $successCount . ' réussies');
        $this->command->info('   - ' . $pendingCount . ' en attente');
        $this->command->info('   - ' . $failedCount . ' échouées');
    }

    // Méthode supprimée - on utilise désormais les wallets créés par WalletSeeder

    private function createTestTransactions($wallet): void
    {
        $transactionTypes = ['deposit', 'withdrawal', 'payment', 'transfer'];
        $statuses = ['pending', 'success', 'failed']; // Correction: 'success' au lieu de 'completed'

        // Créer 2-3 transactions par wallet (5 wallets × 3 = max 15 transactions)
        $count = rand(2, 3);

        for ($i = 0; $i < $count; $i++) {
            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => $transactionTypes[array_rand($transactionTypes)],
                'amount' => rand(1000, 50000),
                'status' => $statuses[array_rand($statuses)],
                'reference' => 'TX_' . strtoupper(uniqid()),
                'meta' => [
                    'description' => 'Transaction de test MongoDB',
                    'created_by' => 'seeder'
                ]
            ]);
        }
    }
}
