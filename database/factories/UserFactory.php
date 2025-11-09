<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * Préfixes téléphoniques sénégalais valides
     */
    private array $senegalPrefixes = ['77', '78', '70', '76', '75'];

    /**
     * Adresses sénégalaises réalistes
     */
    private array $senegalAddresses = [
        'Dakar, Plateau',
        'Dakar, Médina',
        'Dakar, HLM',
        'Thiès, Centre-ville',
        'Saint-Louis, Île',
        'Rufisque, Est',
        'Kaolack, Centre',
        'Ziguinchor, Centre',
        'Diourbel, Médina',
        'Tambacounda, Centre',
        'Dakar, Parcelles Assainies',
        'Dakar, Guédiawaye',
        'Pikine, Thiaroye',
        'Mbour, Centre',
        'Louga, Centre'
    ];

    public function definition(): array
    {
        return [
            'nom' => $this->faker->lastName,
            'prenom' => $this->faker->firstName,
            'type_piece' => $this->faker->randomElement(['cni', 'passport', 'permis']),
            'numero' => $this->generateNumeroDocument(),
            'adresse' => $this->faker->randomElement($this->senegalAddresses),
            'telephone' => $this->generateSenegalPhone(),
            'email' => $this->generateOptionalEmail(), // 70% ont un email
            'code' => '1234', // Sera hashé automatiquement par le mutateur du modèle
            'email_verified_at' => $this->faker->optional(0.5)->dateTime(),
            'login_attempts' => 0,
            'blocked_until' => null,
            'last_login_attempt' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Génère un numéro de téléphone sénégalais valide
     */
    private function generateSenegalPhone(): string
    {
        $prefix = $this->faker->randomElement($this->senegalPrefixes);
        $number = $this->faker->numerify('#######');
        return '+221' . $prefix . $number;
    }

    /**
     * Génère un numéro de document selon le type
     */
    private function generateNumeroDocument(): string
    {
        return match ($this->faker->randomElement(['cni', 'passport', 'permis'])) {
            'cni' => $this->faker->numerify('#-###-###-###-###-##'), // Format CNI sénégalaise
            'passport' => $this->faker->regexify('[A-Z]{2}[0-9]{7}'), // Format passport
            'permis' => $this->faker->numerify('########'), // Numéro permis
            default => $this->faker->numerify('#############')
        };
    }

    /**
     * Génère un email optionnel (70% de chance d'avoir un email)
     */
    private function generateOptionalEmail(): ?string
    {
        return rand(1, 100) <= 70 ? $this->faker->unique()->safeEmail : null;
    }

    /**
     * État : Utilisateur avec un code spécifique
     */
    public function withCode(string $code): static
    {
        return $this->state(fn() => ['code' => $code]);
    }

    /**
     * État : Utilisateur bloqué
     */
    public function blocked(): static
    {
        return $this->state(fn() => [
            'login_attempts' => 3,
            'blocked_until' => now()->addMinutes(30),
            'last_login_attempt' => now()->subMinutes(5)
        ]);
    }

    /**
     * État : Utilisateur avec plusieurs tentatives
     */
    public function withAttempts(int $attempts = 2): static
    {
        return $this->state(fn() => [
            'login_attempts' => $attempts,
            'last_login_attempt' => now()->subMinutes(rand(1, 10))
        ]);
    }

    /**
     * État : Utilisateur email vérifié
     */
    public function emailVerified(): static
    {
        return $this->state(fn() => [
            'email_verified_at' => now(),
            'email' => $this->faker->unique()->safeEmail
        ]);
    }

    /**
     * État : Utilisateur admin/test
     */
    public function admin(): static
    {
        return $this->state(fn() => [
            'nom' => 'Admin',
            'prenom' => 'System',
            'telephone' => '+221771234567',
            'email' => 'admin@om-paie.sn',
            'code' => 'admin123',
            'type_piece' => 'cni',
            'numero' => '1-234-567-890-123-45',
            'adresse' => 'Dakar, Plateau - Siège Social',
            'email_verified_at' => now()
        ]);
    }
}
