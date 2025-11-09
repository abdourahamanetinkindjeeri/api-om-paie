<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class MongoDBSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'telephone' => '221771234567',
                'nom' => 'Jean',
                'prenom' => 'Dupont',
                'email' => 'jean@example.com',
                'balance' => 50000,
            ],
            [
                'telephone' => '221771234568',
                'nom' => 'Marie',
                'prenom' => 'Martin',
                'email' => 'marie@example.com',
                'balance' => 25000,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['telephone' => $data['telephone']],
                $data
            );
        }

        $this->command->info('✅ Utilisateurs MongoDB créés');
    }
}
