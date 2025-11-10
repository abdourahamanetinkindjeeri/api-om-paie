<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerchantTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des marchands de test avec noms africains
        $merchant1 = \App\Models\Merchant::create([
            'name' => 'Boutique Fatou Ndiaye',
            'code' => 'BOUT001',
            'telephone' => '+221775551234',
            'email' => 'fatou.ndiaye@boutique.sn',
            'status' => 'active'
        ]);

        $merchant2 = \App\Models\Merchant::create([
            'name' => 'Pharmacie Aminata Diallo',
            'code' => 'PHAR001',
            'telephone' => '+221776662345',
            'email' => 'aminata.diallo@pharmacie.sn',
            'status' => 'active'
        ]);

        $merchant3 = \App\Models\Merchant::create([
            'name' => 'Restaurant Teranga Mamadou',
            'code' => 'REST001',
            'telephone' => '+221777773456',
            'email' => 'mamadou@teranga.sn',
            'status' => 'active'
        ]);

        $merchant4 = \App\Models\Merchant::create([
            'name' => 'Station Ousmane Sow',
            'code' => 'STAT001',
            'telephone' => '+221778884567',
            'email' => 'ousmane.sow@station.sn',
            'status' => 'active'
        ]);

        $merchant5 = \App\Models\Merchant::create([
            'name' => 'Salon Khady Fall',
            'code' => 'SALON001',
            'telephone' => '+221779995678',
            'email' => 'khady.fall@salon.sn',
            'status' => 'inactive' // Marchand inactif pour les tests
        ]);

        $merchant6 = \App\Models\Merchant::create([
            'name' => 'Atelier Ibrahima Ba',
            'code' => 'ATEL001',
            'telephone' => '+221780001111',
            'email' => 'ibrahima.ba@atelier.sn',
            'status' => 'active'
        ]);

        $merchant7 = \App\Models\Merchant::create([
            'name' => 'Épicerie Aïcha Cissé',
            'code' => 'EPIC001',
            'telephone' => '+221781112222',
            'email' => 'aicha.cisse@epicerie.sn',
            'status' => 'active'
        ]);

        // Créer des wallets pour les marchands
        $merchants = [$merchant1, $merchant2, $merchant3, $merchant4, $merchant5, $merchant6, $merchant7];
        $initialBalances = [0, 15000, 45000, 120000, 0, 75000, 30000]; // Soldes variés

        foreach ($merchants as $index => $merchant) {
            \App\Models\Wallet::create([
                'merchant_id' => $merchant->id,
                'balance' => $initialBalances[$index],
                'currency' => 'XOF'
            ]);
        }

        $this->command->info('Marchands et wallets créés avec succès !');
        $this->command->info('');
        $this->command->info('📊 Marchands de test créés :');
        $this->command->info('  • BOUT001 - Boutique Fatou Ndiaye (Actif - 0 FCFA)');
        $this->command->info('  • PHAR001 - Pharmacie Aminata Diallo (Actif - 15,000 FCFA)');
        $this->command->info('  • REST001 - Restaurant Teranga Mamadou (Actif - 45,000 FCFA)');
        $this->command->info('  • STAT001 - Station Ousmane Sow (Actif - 120,000 FCFA)');
        $this->command->info('  • SALON001 - Salon Khady Fall (Inactif - 0 FCFA)');
        $this->command->info('  • ATEL001 - Atelier Ibrahima Ba (Actif - 75,000 FCFA)');
        $this->command->info('  • EPIC001 - Épicerie Aïcha Cissé (Actif - 30,000 FCFA)');
        $this->command->info('');
        $this->command->info('💡 Utilisez ces codes pour tester les paiements !');
    }
}
