<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionLog>
 */
class TransactionLogFactory extends Factory
{
    /**
     * Actions possibles dans les logs
     */
    private array $actions = [
        'created',
        'validated',
        'processing',
        'completed',
        'failed',
        'cancelled',
        'refunded',
        'disputed'
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = $this->faker->randomElement($this->actions);

        return [
            'action' => $action,
            'description' => $this->generateDescriptionByAction($action),
        ];
    }

    /**
     * Génère une description selon l'action
     */
    private function generateDescriptionByAction(string $action): string
    {
        return match ($action) {
            'created' => 'Transaction initiée par l\'utilisateur',
            'validated' => 'Transaction validée - vérifications OK',
            'processing' => 'Transaction en cours de traitement',
            'completed' => 'Transaction terminée avec succès',
            'failed' => 'Échec de la transaction: ' . $this->faker->randomElement([
                'Solde insuffisant',
                'Erreur système',
                'Timeout réseau',
                'Service indisponible'
            ]),
            'cancelled' => 'Transaction annulée par l\'utilisateur',
            'refunded' => 'Remboursement effectué',
            'disputed' => 'Transaction contestée - en cours d\'investigation',
            default => 'Action système: ' . $action
        };
    }

    /**
     * Log de création
     */
    public function created(): static
    {
        return $this->state(fn() => [
            'action' => 'created',
            'description' => 'Transaction initiée avec succès'
        ]);
    }

    /**
     * Log de completion
     */
    public function completed(): static
    {
        return $this->state(fn() => [
            'action' => 'completed',
            'description' => 'Transaction finalisée avec succès'
        ]);
    }

    /**
     * Log d'erreur
     */
    public function failed(): static
    {
        return $this->state(fn() => [
            'action' => 'failed',
            'description' => 'Échec: ' . $this->faker->randomElement([
                'Solde insuffisant',
                'Erreur de connexion',
                'Service temporairement indisponible'
            ])
        ]);
    }
}
