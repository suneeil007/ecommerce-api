<?php

namespace App\Modules\Permission\Services;

use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function __construct(
        protected PermissionRepositoryInterface $repository
    ) {
    }

    /**
     * Get all permissions.
     */
    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Get a single permission.
     */
    public function getById(int $id): Permission
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a permission.
     */
    public function create(array $data): Permission
    {
        return $this->repository->create($data);
    }

    /**
     * Update a permission.
     */
    public function update(int $id, array $data): Permission
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a permission.
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}

