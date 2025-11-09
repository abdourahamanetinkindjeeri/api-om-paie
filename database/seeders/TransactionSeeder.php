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
        // Pour MongoDB, on va d'abord créer quelques utilisateurs et wallets de test
        $this->createTestUsersAndWallets();

        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            // Créer quelques transactions de test pour MongoDB
            $this->createTestTransactions($wallet);
        }

        $transactionCount = Transaction::count();
        $completedCount = Transaction::where('status', 'completed')->count();
        $pendingCount = Transaction::where('status', 'pending')->count();
        $failedCount = Transaction::where('status', 'failed')->count();

        $this->command->info('✅ ' . $transactionCount . ' transactions créées avec succès');
        $this->command->info('   - ' . $completedCount . ' terminées');
        $this->command->info('   - ' . $pendingCount . ' en attente');
        $this->command->info('   - ' . $failedCount . ' échouées');
    }

    private function createTestUsersAndWallets(): void
    {
        // Créer des utilisateurs de test
        $users = [
            [
                'telephone' => '221771234567',
                'nom' => 'Jean',
                'prenom' => 'Dupont',
                'email' => 'jean@example.com',
                'type_piece' => 'CNI',
                'numero' => '123456789',
                'adresse' => 'Dakar, Sénégal'
            ],
            [
                'telephone' => '221771234568',
                'nom' => 'Marie',
                'prenom' => 'Martin',
                'email' => 'marie@example.com',
                'type_piece' => 'CNI',
                'numero' => '987654321',
                'adresse' => 'Thiès, Sénégal'
            ],
            [
                'telephone' => '221773014729',
                'nom' => 'Pierre',
                'prenom' => 'Sall',
                'email' => 'pierre@example.com',
                'type_piece' => 'CNI',
                'numero' => '456789123',
                'adresse' => 'Saint-Louis, Sénégal'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['telephone' => $userData['telephone']],
                $userData
            );

            // Créer un wallet pour chaque utilisateur
            Wallet::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'balance' => rand(10000, 100000),
                    'currency' => 'XOF'
                ]
            );
        }

        $this->command->info('✅ Utilisateurs et wallets créés');
    }

    private function createTestTransactions($wallet): void
    {
        $transactionTypes = ['deposit', 'withdrawal', 'payment', 'transfer'];
        $statuses = ['completed', 'pending', 'failed'];

        // Créer 3-8 transactions par wallet
        $count = rand(3, 8);

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
