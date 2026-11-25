<?php

namespace App\Modules\Role\Repositories;

use App\Modules\Role\Models\Role;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Get all roles.
     */
    public function all(): Collection
    {
        return Role::query()
            ->with('permissions')
            ->get();
    }

    /**
     * Find a role by ID.
     */
    public function find(int $id): ?Role
    {
        return Role::query()
            ->with('permissions')
            ->find($id);
    }

    /**
     * Find a role by name.
     */
    public function findByName(string $name): ?Role
    {
        return Role::query()
            ->where('name', $name)
            ->first();
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        return Role::create($data);
    }

    /**
     * Update an existing role.
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->fresh();
    }

    /**
     * Delete a role.
     */
    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }
}

