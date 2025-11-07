<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer 10 utilisateurs aléatoires
        User::factory()->count(10)->create();

        // Créer un admin spécifique
        User::factory()->create([
            'nom'       => 'Diallo',
            'prenom'    => 'Abdourahamane',
            'telephone' => '+221770000000',
            'numero'    => '1234567890123',
            'email'     => 'admin@example.com',
            'code'      => bcrypt('1234'),
        ]);
    }
}
