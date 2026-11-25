<?php

namespace App\Modules\Permission\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\Permission\Requests\StorePermissionRequest;
use App\Modules\Permission\Requests\UpdatePermissionRequest;
use App\Modules\Permission\Resources\PermissionResource;
use App\Modules\Permission\Services\PermissionService;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionService $service
    ) {
    }

    /**
     * Display a listing of permissions.
     */
    public function index(): JsonResponse
    {
        $permissions = $this->service->getAll();

        return ApiResponse::success(
            PermissionResource::collection($permissions),
            'Permissions retrieved successfully.'
        );
    }

    /**
     * Store a newly created permission.
     */
    public function store(
        StorePermissionRequest $request
    ): JsonResponse {
        $permission = $this->service->create(
            $request->validated()
        );

        return ApiResponse::success(
            new PermissionResource($permission),
            'Permission created successfully.',
            201
        );
    }

    /**
     * Display the specified permission.
     */
    public function show(int $permission): JsonResponse
    {
        $permission = $this->service->getById($permission);

        return ApiResponse::success(
            new PermissionResource($permission),
            'Permission retrieved successfully.'
        );
    }

    /**
     * Update the specified permission.
     */
    public function update(
        UpdatePermissionRequest $request,
        int $permission
    ): JsonResponse {
        $permission = $this->service->update(
            $permission,
            $request->validated()
        );

        return ApiResponse::success(
            new PermissionResource($permission),
            'Permission updated successfully.'
        );
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(int $permission): JsonResponse
    {
        $this->service->delete($permission);

        return ApiResponse::success(
            null,
            'Permission deleted successfully.'
        );
    }
}

