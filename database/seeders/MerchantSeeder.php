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
        // Marchands actifs principaux
        Merchant::factory()->active()->count(20)->create();

        // Grandes entreprises
        Merchant::factory()->enterprise()->count(5)->create();

        // Marchands en attente de validation
        Merchant::factory()->inactive()->count(8)->create();

        // Marchands inactifs
        Merchant::factory()->inactive()->count(3)->create();

        // Marchands de test spécifiques
        Merchant::factory()->active()->create([
            'name' => 'Boutique Sandaga - Test',
            'code' => 'SND001',
            'telephone' => '+221338901234',
            'email' => 'sandaga@test.sn'
        ]);

        Merchant::factory()->active()->create([
            'name' => 'Pharmacie Plateau',
            'code' => 'PHA001',
            'telephone' => '+221775432100',
            'email' => 'pharmacie.plateau@om-paie.sn'
        ]);

        Merchant::factory()->enterprise()->create([
            'name' => 'Auchan Sénégal',
            'code' => 'AUC001',
            'telephone' => '+221338889999',
            'email' => 'auchan@senegal.sn'
        ]);

        $this->command->info('✅ ' . Merchant::count() . ' marchands créés avec succès');
    }
}
