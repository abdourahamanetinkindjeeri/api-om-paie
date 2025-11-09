<?php

namespace App\Services\Contracts;

/**
 * Contrat générique pour les Services.
 * Ne dépend pas d’Eloquent ni d’un ORM spécifique.
 */
interface BaseServiceInterface
{
    public function list(array $filters = [], int $page = 1, int $limit = 10): mixed;

    public function find(int|string $id): mixed;

    public function create(array $data): mixed;

    public function update(int|string $id, array $data): mixed;

    public function delete(int|string $id): bool;

}
