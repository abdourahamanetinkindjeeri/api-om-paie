<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransferTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des utilisateurs de test pour les transferts
        $user1 = \App\Models\User::create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'type_piece' => 'cni',
            'numero' => '221771234567',
            'adresse' => 'Dakar, Sénégal',
            'code' => '1234', // Sera hashé automatiquement
            'telephone' => '771234567',
            'email' => 'jean.dupont@example.com',
        ]);

        $user2 = \App\Models\User::create([
            'nom' => 'Marie',
            'prenom' => 'Martin',
            'type_piece' => 'cni',
            'numero' => '221771234568',
            'adresse' => 'Thiès, Sénégal',
            'code' => '5678', // Sera hashé automatiquement
            'telephone' => '771234568',
            'email' => 'marie.martin@example.com',
        ]);

        $user3 = \App\Models\User::create([
            'nom' => 'Amadou',
            'prenom' => 'Diallo',
            'type_piece' => 'cni',
            'numero' => '221771234569',
            'adresse' => 'Saint-Louis, Sénégal',
            'code' => '9999', // Sera hashé automatiquement
            'telephone' => '771234569',
            'email' => 'amadou.diallo@example.com',
        ]);

        // Créer les wallets avec des soldes initiaux
        \App\Models\Wallet::create([
            'user_id' => $user1->id,
            'balance' => 50000.00, // 50,000 FCFA
            'currency' => 'XOF'
        ]);

        \App\Models\Wallet::create([
            'user_id' => $user2->id,
            'balance' => 25000.00, // 25,000 FCFA
            'currency' => 'XOF'
        ]);

        \App\Models\Wallet::create([
            'user_id' => $user3->id,
            'balance' => 100000.00, // 100,000 FCFA
            'currency' => 'XOF'
        ]);

        $this->command->info('Utilisateurs et wallets de test créés avec succès !');
        $this->command->info('User 1: ' . $user1->numero . ' (Solde: 50,000 FCFA)');
        $this->command->info('User 2: ' . $user2->numero . ' (Solde: 25,000 FCFA)');
        $this->command->info('User 3: ' . $user3->numero . ' (Solde: 100,000 FCFA)');
    }
}
