<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Transaction, TransactionLog};

class TransactionLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactions = Transaction::all();

        foreach ($transactions as $transaction) {
            // Chaque transaction a au minimum un log de création
            TransactionLog::factory()->created()->create([
                'transaction_id' => $transaction->id
            ]);

            // Logs selon le statut de la transaction
            switch ($transaction->status) {
                case 'completed':
                    // Transaction réussie : created -> processing -> completed
                    TransactionLog::factory()->create([
                        'transaction_id' => $transaction->id,
                        'action' => 'processing',
                        'description' => 'Transaction en cours de traitement'
                    ]);

                    TransactionLog::factory()->completed()->create([
                        'transaction_id' => $transaction->id
                    ]);
                    break;

                case 'failed':
                    // Transaction échouée : created -> processing -> failed
                    TransactionLog::factory()->create([
                        'transaction_id' => $transaction->id,
                        'action' => 'processing',
                        'description' => 'Tentative de traitement'
                    ]);

                    TransactionLog::factory()->failed()->create([
                        'transaction_id' => $transaction->id
                    ]);
                    break;

                case 'pending':
                    // Transaction en attente : created -> validated
                    TransactionLog::factory()->create([
                        'transaction_id' => $transaction->id,
                        'action' => 'validated',
                        'description' => 'Transaction validée - en attente de traitement'
                    ]);
                    break;

                case 'cancelled':
                    // Transaction annulée
                    TransactionLog::factory()->create([
                        'transaction_id' => $transaction->id,
                        'action' => 'cancelled',
                        'description' => 'Transaction annulée par l\'utilisateur'
                    ]);
                    break;
            }

            // 20% de chance d'avoir des logs supplémentaires
            if (rand(1, 100) <= 20) {
                TransactionLog::factory()->create([
                    'transaction_id' => $transaction->id,
                    'action' => 'system_check',
                    'description' => 'Vérification automatique du système'
                ]);
            }
        }

        $this->command->info('✅ ' . TransactionLog::count() . ' logs de transactions créés');
    }
}
