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
                'nom' => 'Diallo',
                'prenom' => 'Amadou',
                'email' => 'amadou.diallo@example.com',
                'balance' => 50000,
            ],
            [
                'telephone' => '221771234568',
                'nom' => 'Ndiaye',
                'prenom' => 'Fatou',
                'email' => 'fatou.ndiaye@example.com',
                'balance' => 25000,
            ],
            [
                'telephone' => '221771234569',
                'nom' => 'Ba',
                'prenom' => 'Ibrahima',
                'email' => 'ibrahima.ba@example.com',
                'balance' => 75000,
            ],
            [
                'telephone' => '221771234570',
                'nom' => 'Cissé',
                'prenom' => 'Mariama',
                'email' => 'mariama.cisse@example.com',
                'balance' => 30000,
            ],
            [
                'telephone' => '221771234571',
                'nom' => 'Fall',
                'prenom' => 'Khady',
                'email' => 'khady.fall@example.com',
                'balance' => 60000,
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
