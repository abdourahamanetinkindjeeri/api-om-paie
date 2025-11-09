<?php

namespace App\Repositories\Contracts;

/**
 * Contrat générique pour un Repository.
 * Ne dépend d'aucune implémentation (Eloquent, Doctrine, API...).
 */
interface BaseRepositoryInterface
{
    /**
     * Récupérer une liste paginée d'entités.
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return mixed  // L’implémentation choisira : Paginator, Array, Collection...
     */
    public function all(array $filters = [], int $page = 1, int $limit = 10): mixed;

    /**
     * Trouver une entité par son identifiant.
     */
    public function find(int|string $id): mixed;

    /**
     * Créer une nouvelle entité.
     */
    public function create(array $data): mixed;

    /**
     * Mettre à jour une entité existante.
     */
    public function update(int|string $id, array $data): mixed;

    /**
     * Supprimer une entité.
     */
    public function delete(int|string $id): bool;

}
