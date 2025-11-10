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
            'nom' => 'Touré',
            'prenom' => 'Amadou',
            'telephone' => '+221773014729',
            'email' => 'amadou.toure@om-paie.sn'
        ]);

        User::factory()->emailVerified()->create([
            'nom' => 'Diallo',
            'prenom' => 'Abdourahamane',
            'telephone' => '+221770000000',
            'email' => 'abdourahamane.diallo@om-paie.sn',
            'code' => '0000'
        ]);

        // Utilisateurs supplémentaires avec noms africains (5 maximum pour rester sous la limite de 10)
        User::factory()->create([
            'nom' => 'Seck',
            'prenom' => 'Fatou',
            'telephone' => '+221775555555',
            'code' => '5555',
            'email' => 'fatou.seck@om-paie.sn'
        ]);

        User::factory()->create([
            'nom' => 'Ndiaye',
            'prenom' => 'Mamadou',
            'telephone' => '+221776666666',
            'code' => '6666',
            'type_piece' => 'passport',
            'numero' => 'SN1234567',
            'email' => 'mamadou.ndiaye@om-paie.sn'
        ]);

        User::factory()->create([
            'nom' => 'Ba',
            'prenom' => 'Aïssatou',
            'telephone' => '+221777777777',
            'code' => '7777',
            'email' => 'aissatou.ba@om-paie.sn'
        ]);

        User::factory()->create([
            'nom' => 'Cissé',
            'prenom' => 'Ousmane',
            'telephone' => '+221778888888',
            'code' => '8888',
            'email' => 'ousmane.cisse@om-paie.sn'
        ]);

        User::factory()->emailVerified()->create([
            'nom' => 'Fall',
            'prenom' => 'Khady',
            'telephone' => '+221779999999',
            'code' => '9999',
            'email' => 'khady.fall@om-paie.sn'
        ]);

        // Un utilisateur avec tentatives de connexion pour les tests
        User::factory()->withAttempts(1)->create([
            'nom' => 'Sarr',
            'prenom' => 'Ibrahima',
            'telephone' => '+221771111111',
            'code' => '1111',
            'email' => 'ibrahima.sarr@om-paie.sn'
        ]);

        $this->command->info('✅ ' . User::count() . ' utilisateurs créés avec succès');
    }
}
