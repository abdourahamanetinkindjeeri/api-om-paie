<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Types de transactions
     */
    private array $transactionTypes = [
        'deposit',      // Dépôt
        'withdrawal',   // Retrait
        'transfer',     // Transfert
        'payment',      // Paiement marchand
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement($this->transactionTypes);

        return [
            'type' => $type,
            'amount' => $this->generateAmountByType($type),
            'status' => $this->faker->randomElement(['pending', 'success', 'failed']),
            'reference' => $this->generateReference(),
            'meta' => $this->generateMetaByType($type),
        ];
    }

    /**
     * Génère un montant selon le type de transaction
     */
    private function generateAmountByType(string $type): float
    {
        return match ($type) {
            'deposit' => $this->faker->randomFloat(2, 1000, 500000), // 1K à 500K
            'withdrawal' => $this->faker->randomFloat(2, 500, 100000), // 500 à 100K
            'transfer' => $this->faker->randomFloat(2, 1000, 200000), // 1K à 200K
            'payment' => $this->faker->randomFloat(2, 100, 50000), // 100 à 50K
            default => $this->faker->randomFloat(2, 100, 10000)
        };
    }

    /**
     * Génère une référence de transaction
     */
    private function generateReference(): string
    {
        return 'OM' . date('Ymd') . strtoupper($this->faker->lexify('???')) . $this->faker->numerify('####');
    }

    /**
     * Génère des métadonnées selon le type
     */
    private function generateMetaByType(string $type): array
    {
        $baseMeta = [
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_by' => 'system'
        ];

        return match ($type) {
            'deposit' => array_merge($baseMeta, [
                'source' => $this->faker->randomElement(['bank_transfer', 'mobile_money', 'cash']),
                'bank_reference' => 'BNK' . $this->faker->numerify('############')
            ]),
            'withdrawal' => array_merge($baseMeta, [
                'destination' => $this->faker->randomElement(['bank_account', 'mobile_money', 'cash']),
                'agent_id' => 'AGT' . $this->faker->numerify('####')
            ]),
            'transfer' => array_merge($baseMeta, [
                'recipient_phone' => '+221' . $this->faker->numerify('77#######'),
                'message' => $this->faker->optional()->sentence(6)
            ]),
            'payment' => array_merge($baseMeta, [
                'merchant_reference' => 'MRC' . $this->faker->numerify('########'),
                'product_info' => $this->faker->sentence(4)
            ]),
            default => $baseMeta
        };
    }

    /**
     * Transaction réussie
     */
    public function completed(): static
    {
        return $this->state(fn() => ['status' => 'success']);
    }

    /**
     * Transaction en attente
     */
    public function pending(): static
    {
        return $this->state(fn() => ['status' => 'pending']);
    }

    /**
     * Transaction échouée
     */
    public function failed(): static
    {
        return $this->state(fn() => ['status' => 'failed']);
    }

    /**
     * Dépôt
     */
    public function deposit(): static
    {
        return $this->state(fn() => [
            'type' => 'deposit',
            'amount' => $this->faker->randomFloat(2, 5000, 1000000),
            'status' => 'success'
        ]);
    }

    /**
     * Retrait
     */
    public function withdrawal(): static
    {
        return $this->state(fn() => [
            'type' => 'withdrawal',
            'amount' => $this->faker->randomFloat(2, 1000, 500000)
        ]);
    }

    /**
     * Paiement marchand
     */
    public function payment(): static
    {
        return $this->state(fn() => [
            'type' => 'payment',
            'amount' => $this->faker->randomFloat(2, 500, 100000),
            'status' => 'success'
        ]);
    }
}
