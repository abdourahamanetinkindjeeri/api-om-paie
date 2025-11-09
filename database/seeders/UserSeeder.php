<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisateur Admin système
        User::factory()->admin()->create();

        // Utilisateurs de test avec codes spécifiques
        User::factory()->withCode('1234')->create([
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '+221773014729',
            'email' => 'test@om-paie.sn'
        ]);

        User::factory()->emailVerified()->create([
            'nom' => 'Diallo',
            'prenom' => 'Abdourahamane',
            'telephone' => '+221770000000',
            'email' => 'diallo@om-paie.sn',
            'code' => '0000'
        ]);

        // Utilisateurs normaux avec différents états
        User::factory()->count(15)->create(); // Utilisateurs normaux
        User::factory()->count(3)->emailVerified()->create(); // Avec email vérifié
        User::factory()->count(2)->withAttempts(1)->create(); // Avec 1 tentative
        User::factory()->count(2)->withAttempts(2)->create(); // Avec 2 tentatives
        User::factory()->count(1)->blocked()->create(); // Compte bloqué

        // Utilisateurs pour tests spécifiques
        User::factory()->create([
            'nom' => 'Seck',
            'prenom' => 'Fatou',
            'telephone' => '+221775555555',
            'code' => '5555',
            'email' => 'fatou.seck@example.com'
        ]);

        User::factory()->create([
            'nom' => 'Ndiaye',
            'prenom' => 'Mamadou',
            'telephone' => '+221776666666',
            'code' => '6666',
            'type_piece' => 'passport',
            'numero' => 'SN1234567'
        ]);

        $this->command->info('✅ ' . User::count() . ' utilisateurs créés avec succès');
    }
}
