<?php

namespace App\Services;

use App\Services\Contracts\BaseServiceInterface;
use App\Repositories\Contracts\BaseRepositoryInterface;

abstract class BaseService implements BaseServiceInterface
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function list(array $filters = [], int $page = 1, int $limit = 10): mixed
    {
        return $this->repository->all($filters, $page, $limit);
    }

    public function find(int|string $id): mixed
    {
        return $this->repository->find($id);
    }

    public function create(array $data): mixed
    {
        // Ici tu peux appliquer des règles métier génériques (ex: validation)
        return $this->repository->create($data);
    }

    public function update(int|string $id, array $data): mixed
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int|string $id): bool
    {
        return $this->repository->delete($id);
    }
}
