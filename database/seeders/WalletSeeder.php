<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Merchant, Wallet};

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $merchants = Merchant::where('status', 'active')->get();

        // Créer un wallet pour chaque utilisateur
        foreach ($users as $user) {
            if ($user->nom === 'Admin') {
                // Wallet admin avec beaucoup d'argent
                Wallet::factory()->wealthy()->forUser($user->id)->create();
            } elseif ($user->telephone === '+221773014729') {
                // Wallet de test avec solde moyen
                Wallet::factory()->forUser($user->id)->create(['balance' => 50000]);
            } else {
                // Wallets normaux avec soldes variables
                $factory = Wallet::factory()->forUser($user->id);

                if (rand(1, 10) <= 2) {
                    // 20% de wallets vides ou presque
                    $factory = $factory->poorBalance();
                } elseif (rand(1, 10) <= 1) {
                    // 10% de wallets riches
                    $factory = $factory->wealthy();
                }

                $factory->create();
            }
        }

        // Créer un wallet pour chaque marchand actif
        foreach ($merchants as $merchant) {
            if (str_contains($merchant->name, 'Auchan') || str_contains($merchant->name, 'Entreprise')) {
                // Grandes entreprises = gros soldes
                Wallet::factory()->wealthy()->forMerchant($merchant->id)->create();
            } else {
                // Marchands normaux
                Wallet::factory()->forMerchant($merchant->id)->create();
            }
        }

        $this->command->info('✅ ' . Wallet::count() . ' wallets créés avec succès');
        $this->command->info('   - ' . Wallet::whereNotNull('user_id')->count() . ' wallets utilisateurs');
        $this->command->info('   - ' . Wallet::whereNotNull('merchant_id')->count() . ' wallets marchands');
    }
}
