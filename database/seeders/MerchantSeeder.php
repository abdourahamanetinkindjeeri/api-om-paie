<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Merchant;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🚀 Création des marchands...\n";

        // Créer quelques marchands manuellement pour tester
        $merchants = [
            [
                'name' => 'Boutique Sandaga - Test',
                'code' => 'SND001',
                'telephone' => '+221338901234',
                'email' => 'sandaga@test.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Pharmacie Plateau',
                'code' => 'PHA001',
                'telephone' => '+221775432100',
                'email' => 'pharmacie.plateau@om-paie.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Auchan Sénégal',
                'code' => 'AUC001',
                'telephone' => '+221338889999',
                'email' => 'auchan@senegal.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Restaurant Teranga',
                'code' => 'RES001',
                'telephone' => '+221776543210',
                'email' => 'teranga@restaurant.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Cyber Café Digital',
                'code' => 'CYB001',
                'telephone' => '+221781234567',
                'email' => null,
                'status' => 'inactive'
            ]
        ];

        foreach ($merchants as $merchantData) {
            Merchant::create($merchantData);
        }

        $this->command->info('✅ ' . Merchant::count() . ' marchands créés avec succès');
    }
}
