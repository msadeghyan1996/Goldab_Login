<?php
namespace App\Repositories\impl;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();
        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }
        return $query->get($columns);
    }
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();
        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }
        return $query->first($columns);
    }
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }
    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);
        if (!$record) {
            return false;
        }
        return $record->update($data);
    }
    public function delete(int $id): bool
    {
        $record = $this->find($id);
        if (!$record) {
            return false;
        }
        return $record->delete();
    }
    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }
    protected function beginTransaction(): void
    {
        $this->model->getConnection()->beginTransaction();
    }
    protected function commit(): void
    {
        $this->model->getConnection()->commit();
    }
    protected function rollback(): void
    {
        $this->model->getConnection()->rollBack();
    }
}