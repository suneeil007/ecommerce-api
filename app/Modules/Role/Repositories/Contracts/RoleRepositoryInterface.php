<?php

namespace App\Modules\Role\Repositories\Contracts;

use App\Modules\Role\Models\Role;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    /**
     * Get all roles.
     */
    public function all(): Collection;

    /**
     * Find a role by ID.
     */
    public function find(int $id): ?Role;

    /**
     * Find a role by name.
     */
    public function findByName(string $name): ?Role;

    /**
     * Create a new role.
     */
    public function create(array $data): Role;

    /**
     * Update an existing role.
     */
    public function update(Role $role, array $data): Role;

    /**
     * Delete a role.
     */
    public function delete(Role $role): bool;
}

