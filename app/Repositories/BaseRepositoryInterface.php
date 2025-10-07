<?php
namespace App\Repositories;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;
    public function find(int $id, array $columns = ['*']): ?Model;
    public function findOrFail(int $id, array $columns = ['*']): Model;
    public function findBy(array $criteria, array $columns = ['*']): Collection;
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15, array $columns = ['*']);
    public function updateOrCreate(array $attributes, array $values = []): Model;
}