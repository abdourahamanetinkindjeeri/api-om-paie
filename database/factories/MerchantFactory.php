<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Merchant>
 */
class MerchantFactory extends Factory
{
    /**
     * Types de commerces sénégalais
     */
    private array $merchantTypes = [
        'Boutique',
        'Pharmacie',
        'Restaurant',
        'Boulangerie',
        'Station Service',
        'Supérette',
        'Quincaillerie',
        'Cyber Café',
        'Salon de Coiffure',
        'Magasin de Tissus',
        'Electroménager',
        'Librairie',
        'Transport'
    ];

    /**
     * Quartiers/zones commerciales de Dakar
     */
    private array $businessAreas = [
        'Plateau',
        'Sandaga',
        'Kermel',
        'Médina',
        'HLM',
        'Parcelles Assainies',
        'Guédiawaye',
        'Pikine',
        'Rufisque',
        'Grand Dakar',
        'Point E',
        'Almadies'
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessType = $this->faker->randomElement($this->merchantTypes);
        $area = $this->faker->randomElement($this->businessAreas);

        return [
            'name' => "$businessType {$this->faker->lastName} - $area",
            'code' => strtoupper($this->faker->lexify('???')) . $this->faker->numerify('###'),
            'telephone' => $this->generateSenegalPhone(),
            'email' => $this->faker->optional(0.6)->companyEmail(), // 60% ont un email
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    /**
     * Génère un numéro de téléphone sénégalais pour entreprise
     */
    private function generateSenegalPhone(): string
    {
        $prefixes = ['77', '78', '70', '76', '75', '33']; // Inclut les fixes
        $prefix = $this->faker->randomElement($prefixes);
        $number = $this->faker->numerify('#######');
        return '+221' . $prefix . $number;
    }

    /**
     * Marchand actif
     */
    public function active(): static
    {
        return $this->state(fn() => ['status' => 'active']);
    }

    /**
     * Marchand inactif
     */
    public function inactive(): static
    {
        return $this->state(fn() => ['status' => 'inactive']);
    }

    /**
     * Grande entreprise avec email obligatoire
     */
    public function enterprise(): static
    {
        return $this->state(fn() => [
            'name' => 'Entreprise ' . $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'status' => 'active'
        ]);
    }
}
