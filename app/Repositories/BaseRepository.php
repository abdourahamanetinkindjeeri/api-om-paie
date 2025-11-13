<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Events\EntitySaved;
use App\Events\EntityDeleted;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    /**
     * Get the model instance
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    public function all(array $filters = [], int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function find(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function create(array $data, $session = null): Model
    {
        if ($session) {
            // Utiliser la session MongoDB pour les transactions
            $entity = $this->model->create($data, ['session' => $session]);
        } else {
            $entity = $this->model->create($data);
        }

        event(new EntitySaved($entity));
        return $entity;
    }

    public function update(int|string $id, array $data): ?Model
    {
        $entity = $this->find($id);
        if ($entity) {
            $entity->update($data);
            event(new EntitySaved($entity)); // déclenchement event
        }
        return $entity;
    }

    public function delete(int|string $id): bool
    {
        $entity = $this->find($id);
        if ($entity) {
            $deleted = (bool) $entity->delete();
            if ($deleted) {
                event(new EntityDeleted($entity)); // déclenchement event
            }
            return $deleted;
        }
        return false;
    }
}
