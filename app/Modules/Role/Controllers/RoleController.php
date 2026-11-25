<?php

namespace App\Modules\Role\Controllers;

use App\Helpers\ApiResponse;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Requests\StoreRoleRequest;
use App\Modules\Role\Requests\UpdateRoleRequest;
use App\Modules\Role\Resources\RoleResource;
use App\Modules\Role\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController
{
    public function __construct(
        protected RoleService $roleService
    ) {
    }

    /**
     * Display a listing of roles.
     */
    public function index(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();

        return ApiResponse::success(
            RoleResource::collection($roles),
            'Roles retrieved successfully.'
        );
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole(
            $request->validated()
        );

        return ApiResponse::success(
            new RoleResource($role),
            'Role created successfully.',
            201
        );
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return ApiResponse::success(
            new RoleResource($role),
            'Role retrieved successfully.'
        );
    }

    /**
     * Update the specified role.
     */
    public function update(
        UpdateRoleRequest $request,
        Role $role
    ): JsonResponse {
        $role = $this->roleService->updateRole(
            $role,
            $request->validated()
        );

        return ApiResponse::success(
            new RoleResource($role),
            'Role updated successfully.'
        );
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->roleService->deleteRole($role);

        return ApiResponse::success(
            null,
            'Role deleted successfully.'
        );
    }
}

