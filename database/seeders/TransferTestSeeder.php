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
        // Créer des utilisateurs de test pour les transferts avec noms africains
        $user1 = \App\Models\User::create([
            'nom' => 'Diallo',
            'prenom' => 'Amadou',
            'type_piece' => 'cni',
            'numero' => '221771234567',
            'adresse' => 'Dakar, Sénégal',
            'code' => '1234', // Sera hashé automatiquement
            'telephone' => '771234567',
            'email' => 'amadou.diallo@om-paie.sn',
        ]);

        $user2 = \App\Models\User::create([
            'nom' => 'Ndiaye',
            'prenom' => 'Fatou',
            'type_piece' => 'cni',
            'numero' => '221771234568',
            'adresse' => 'Thiès, Sénégal',
            'code' => '5678', // Sera hashé automatiquement
            'telephone' => '771234568',
            'email' => 'fatou.ndiaye@om-paie.sn',
        ]);

        $user3 = \App\Models\User::create([
            'nom' => 'Cissé',
            'prenom' => 'Ousmane',
            'type_piece' => 'cni',
            'numero' => '221771234569',
            'adresse' => 'Saint-Louis, Sénégal',
            'code' => '9999', // Sera hashé automatiquement
            'telephone' => '771234569',
            'email' => 'ousmane.cisse@om-paie.sn',
        ]);

        $user4 = \App\Models\User::create([
            'nom' => 'Ba',
            'prenom' => 'Aïssatou',
            'type_piece' => 'cni',
            'numero' => '221771234570',
            'adresse' => 'Kaolack, Sénégal',
            'code' => '1111', // Sera hashé automatiquement
            'telephone' => '771234570',
            'email' => 'aissatou.ba@om-paie.sn',
        ]);

        $user5 = \App\Models\User::create([
            'nom' => 'Sow',
            'prenom' => 'Mamadou',
            'type_piece' => 'passport',
            'numero' => 'SN1234570',
            'adresse' => 'Ziguinchor, Sénégal',
            'code' => '2222', // Sera hashé automatiquement
            'telephone' => '771234571',
            'email' => 'mamadou.sow@om-paie.sn',
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

        \App\Models\Wallet::create([
            'user_id' => $user4->id,
            'balance' => 75000.00, // 75,000 FCFA
            'currency' => 'XOF'
        ]);

        \App\Models\Wallet::create([
            'user_id' => $user5->id,
            'balance' => 120000.00, // 120,000 FCFA
            'currency' => 'XOF'
        ]);

        $this->command->info('Utilisateurs et wallets de test créés avec succès !');
        $this->command->info('📊 Utilisateurs créés :');
        $this->command->info('  • Amadou Diallo: ' . $user1->numero . ' (Solde: 50,000 FCFA)');
        $this->command->info('  • Fatou Ndiaye: ' . $user2->numero . ' (Solde: 25,000 FCFA)');
        $this->command->info('  • Ousmane Cissé: ' . $user3->numero . ' (Solde: 100,000 FCFA)');
        $this->command->info('  • Aïssatou Ba: ' . $user4->numero . ' (Solde: 75,000 FCFA)');
        $this->command->info('  • Mamadou Sow: ' . $user5->numero . ' (Solde: 120,000 FCFA)');
        $this->command->info('💡 Utilisez ces numéros pour tester les transferts !');
    }
}
