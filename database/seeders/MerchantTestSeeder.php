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
        // Créer des marchands de test
        $merchant1 = \App\Models\Merchant::create([
            'name' => 'Boutique Fatou',
            'code' => 'BOUT001',
            'telephone' => '+221775551234',
            'email' => 'fatou@boutique.sn',
            'status' => 'active'
        ]);

        $merchant2 = \App\Models\Merchant::create([
            'name' => 'Pharmacie Al Azhar',
            'code' => 'PHAR001',
            'telephone' => '+221776662345',
            'email' => 'contact@alazhar.sn',
            'status' => 'active'
        ]);

        $merchant3 = \App\Models\Merchant::create([
            'name' => 'Restaurant Teranga',
            'code' => 'REST001',
            'telephone' => '+221777773456',
            'email' => 'info@teranga.sn',
            'status' => 'active'
        ]);

        $merchant4 = \App\Models\Merchant::create([
            'name' => 'Station Essence Total',
            'code' => 'STAT001',
            'telephone' => '+221778884567',
            'email' => 'station@total.sn',
            'status' => 'active'
        ]);

        $merchant5 = \App\Models\Merchant::create([
            'name' => 'Salon de Coiffure Elegance',
            'code' => 'SALON001',
            'telephone' => '+221779995678',
            'email' => 'elegance@salon.sn',
            'status' => 'inactive' // Marchand inactif pour les tests
        ]);

        // Créer des wallets pour les marchands
        $merchants = [$merchant1, $merchant2, $merchant3, $merchant4, $merchant5];
        $initialBalances = [0, 15000, 45000, 120000, 0]; // Soldes variés

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
        $this->command->info('  • BOUT001 - Boutique Fatou (Actif - 0 FCFA)');
        $this->command->info('  • PHAR001 - Pharmacie Al Azhar (Actif - 15,000 FCFA)');
        $this->command->info('  • REST001 - Restaurant Teranga (Actif - 45,000 FCFA)');
        $this->command->info('  • STAT001 - Station Essence Total (Actif - 120,000 FCFA)');
        $this->command->info('  • SALON001 - Salon de Coiffure Elegance (Inactif - 0 FCFA)');
        $this->command->info('');
        $this->command->info('💡 Utilisez ces codes pour tester les paiements !');
    }
}
