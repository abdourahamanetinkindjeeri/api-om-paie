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
        echo "🚀 Création des logs de transactions...\n";

        $transactions = Transaction::all();
        $logCount = 0;

        foreach ($transactions as $transaction) {
            // Log de création pour chaque transaction
            TransactionLog::create([
                'transaction_id' => $transaction->id,
                'action' => 'created',
                'description' => 'Transaction créée'
            ]);
            $logCount++;

            // Logs selon le statut de la transaction
            switch ($transaction->status) {
                case 'completed':
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'processing',
                        'description' => 'Transaction en cours de traitement'
                    ]);
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'completed',
                        'description' => 'Transaction terminée avec succès'
                    ]);
                    $logCount += 2;
                    break;

                case 'failed':
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'processing',
                        'description' => 'Tentative de traitement'
                    ]);
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'failed',
                        'description' => 'Échec de la transaction'
                    ]);
                    $logCount += 2;
                    break;

                case 'pending':
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'validated',
                        'description' => 'Transaction validée - en attente de traitement'
                    ]);
                    $logCount++;
                    break;

                case 'cancelled':
                    TransactionLog::create([
                        'transaction_id' => $transaction->id,
                        'action' => 'cancelled',
                        'description' => 'Transaction annulée par l\'utilisateur'
                    ]);
                    $logCount++;
                    break;
            }
        }

        $this->command->info('✅ ' . $logCount . ' logs de transactions créés');
    }
}
