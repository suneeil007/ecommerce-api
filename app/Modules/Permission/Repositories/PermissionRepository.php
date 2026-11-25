<?php

namespace App\Modules\Permission\Repositories;

use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function all(): Collection
    {
        return Permission::with('roles')->get();
    }

    public function findById(int $id): Permission
    {
        return Permission::with('roles')->findOrFail($id);
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(int $id, array $data): Permission
    {
        $permission = Permission::findOrFail($id);

        $permission->update($data);

        return $permission->fresh('roles');
    }

    public function delete(int $id): bool
    {
        return Permission::findOrFail($id)->delete();
    }
}

