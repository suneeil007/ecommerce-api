<?php

namespace App\Modules\Role\Services;

use App\Modules\Role\Models\Role;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function __construct(
        protected RoleRepositoryInterface $roleRepository
    ) {
    }

    /**
     * Get all roles.
     */
    public function getAllRoles(): Collection
    {
        return $this->roleRepository->all();
    }

    /**
     * Get a single role.
     */
    public function getRole(int $id): Role
    {
        $role = $this->roleRepository->find($id);

        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'Role not found.',
            ]);
        }

        return $role;
    }

    /**
     * Create a new role.
     */
    public function createRole(array $data): Role
    {
        $existingRole = $this->roleRepository->findByName(
            $data['name']
        );

        if ($existingRole) {
            throw ValidationException::withMessages([
                'name' => 'A role with this name already exists.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            return $this->roleRepository->create($data);
        });
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Role $role, array $data): Role
    {
        if (
            isset($data['name']) &&
            $data['name'] !== $role->name
        ) {
            $existingRole = $this->roleRepository->findByName(
                $data['name']
            );

            if (
                $existingRole &&
                $existingRole->id !== $role->id
            ) {
                throw ValidationException::withMessages([
                    'name' => 'A role with this name already exists.',
                ]);
            }
        }

        return DB::transaction(function () use ($role, $data) {
            return $this->roleRepository->update($role, $data);
        });
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): bool
    {
        return DB::transaction(function () use ($role) {
            /*
             * Remove role relationships first.
             */
            $role->permissions()->detach();
            $role->users()->detach();

            return $this->roleRepository->delete($role);
        });
    }

    /**
     * Assign permissions to a role.
     */
    public function syncPermissions(
        Role $role,
        array $permissionIds
    ): Role {
        DB::transaction(function () use ($role, $permissionIds) {
            $role->permissions()->sync($permissionIds);
        });

        return $role->fresh([
            'permissions',
        ]);
    }

    /**
     * Assign users to a role.
     */
    public function syncUsers(
        Role $role,
        array $userIds
    ): Role {
        DB::transaction(function () use ($role, $userIds) {
            $role->users()->sync($userIds);
        });

        return $role->fresh([
            'users',
        ]);
    }
}

