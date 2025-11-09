<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Wallet, Transaction};

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            // Nombre aléatoire de transactions par wallet (0 à 15)
            $transactionCount = rand(0, 15);

            if ($transactionCount > 0) {
                // Répartition des types de transactions
                $deposits = rand(1, 3); // 1-3 dépôts
                $withdrawals = rand(0, 2); // 0-2 retraits
                $payments = rand(0, 5); // 0-5 paiements
                $transfers = rand(0, 3); // 0-3 transferts

                // Créer les dépôts (toujours completed pour avoir du solde)
                Transaction::factory()
                    ->deposit()
                    ->count($deposits)
                    ->create(['wallet_id' => $wallet->id]);

                // Créer les retraits
                if ($withdrawals > 0) {
                    Transaction::factory()
                        ->withdrawal()
                        ->count($withdrawals)
                        ->create(['wallet_id' => $wallet->id]);
                }

                // Créer les paiements
                if ($payments > 0) {
                    Transaction::factory()
                        ->payment()
                        ->count($payments)
                        ->create(['wallet_id' => $wallet->id]);
                }

                // Créer les transferts
                if ($transfers > 0) {
                    Transaction::factory()
                        ->count($transfers)
                        ->state(['type' => 'transfer'])
                        ->create(['wallet_id' => $wallet->id]);
                }

                // Quelques transactions échouées
                if (rand(1, 10) <= 3) { // 30% de chance
                    Transaction::factory()
                        ->failed()
                        ->create(['wallet_id' => $wallet->id]);
                }

                // Quelques transactions en attente
                if (rand(1, 10) <= 2) { // 20% de chance
                    Transaction::factory()
                        ->pending()
                        ->create(['wallet_id' => $wallet->id]);
                }
            }
        }

        // Transactions spéciales pour le wallet de test
        $testWallet = Wallet::whereHas('user', function ($q) {
            $q->where('telephone', '+221773014729');
        })->first();

        if ($testWallet) {
            // Historique varié pour les tests
            Transaction::factory()->deposit()->create([
                'wallet_id' => $testWallet->id,
                'amount' => 100000,
                'reference' => 'TEST_DEPOSIT_001'
            ]);

            Transaction::factory()->payment()->create([
                'wallet_id' => $testWallet->id,
                'amount' => 15000,
                'reference' => 'TEST_PAYMENT_001'
            ]);

            Transaction::factory()->withdrawal()->create([
                'wallet_id' => $testWallet->id,
                'amount' => 25000,
                'reference' => 'TEST_WITHDRAWAL_001'
            ]);
        }

        $this->command->info('✅ ' . Transaction::count() . ' transactions créées avec succès');
        $this->command->info('   - ' . Transaction::where('status', 'completed')->count() . ' terminées');
        $this->command->info('   - ' . Transaction::where('status', 'pending')->count() . ' en attente');
        $this->command->info('   - ' . Transaction::where('status', 'failed')->count() . ' échouées');
    }
}
