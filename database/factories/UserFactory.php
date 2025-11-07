<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'        => $this->faker->lastName,
            'prenom'     => $this->faker->firstName,
            'type_piece' => $this->faker->randomElement(['cin', 'passport']),
            'numero'     => $this->faker->numerify('#############'), // 13 chiffres
            'adresse'    => $this->faker->address,
            'telephone'  => '+221' . $this->faker->numerify('77#######'), // format Sénégal
            'email'      => $this->faker->unique()->safeEmail,
            'code'       => Hash::make('1234'), // PIN par défaut
            'remember_token' => Str::random(10),
        ];
    }
}
