<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'balance' => $this->faker->randomFloat(2, 0, 1000000), // Solde entre 0 et 1M FCFA
            'currency' => 'XOF', // Franc CFA
        ];
    }

    /**
     * Wallet pour un utilisateur spécifique
     */
    public function forUser($userId): static
    {
        return $this->state(fn() => [
            'user_id' => $userId,
            'merchant_id' => null,
        ]);
    }

    /**
     * Wallet pour un marchand spécifique
     */
    public function forMerchant($merchantId): static
    {
        return $this->state(fn() => [
            'user_id' => null,
            'merchant_id' => $merchantId,
        ]);
    }

    /**
     * Wallet avec un solde élevé
     */
    public function wealthy(): static
    {
        return $this->state(fn() => [
            'balance' => $this->faker->randomFloat(2, 500000, 5000000), // 500K à 5M FCFA
        ]);
    }

    /**
     * Wallet avec un petit solde
     */
    public function poorBalance(): static
    {
        return $this->state(fn() => [
            'balance' => $this->faker->randomFloat(2, 0, 10000), // 0 à 10K FCFA
        ]);
    }

    /**
     * Wallet vide
     */
    public function empty(): static
    {
        return $this->state(fn() => [
            'balance' => 0,
        ]);
    }
}
