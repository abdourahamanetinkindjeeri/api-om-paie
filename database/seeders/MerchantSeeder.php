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

        // Créer 5 marchands principaux avec noms africains
        $merchants = [
            [
                'name' => 'Boutique Diallo & Fils',
                'code' => 'DIA001',
                'telephone' => '+221338901234',
                'email' => 'contact@diallofils.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Pharmacie Aminata Ba',
                'code' => 'PHA001',
                'telephone' => '+221775432100',
                'email' => 'aminata.ba@pharmacie.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Supermarché Mamadou Cissé',
                'code' => 'SUP001',
                'telephone' => '+221338889999',
                'email' => 'mamadou.cisse@market.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Restaurant Teranga Fatou',
                'code' => 'RES001',
                'telephone' => '+221776543210',
                'email' => 'fatou@teranga.sn',
                'status' => 'active'
            ],
            [
                'name' => 'Atelier Couture Khady Fall',
                'code' => 'COU001',
                'telephone' => '+221781234567',
                'email' => 'khady.fall@couture.sn',
                'status' => 'active'
            ]
        ];

        foreach ($merchants as $merchantData) {
            Merchant::create($merchantData);
        }

        $this->command->info('✅ ' . Merchant::count() . ' marchands créés avec succès');
    }
}
