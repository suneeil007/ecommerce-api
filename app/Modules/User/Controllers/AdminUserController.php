<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\User\Services\UserService;
use App\Modules\User\Requests\ListUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Requests\UpdateCustomerStatusRequest;
use App\Modules\User\Requests\ListCustomerOrderRequest;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(ListUserRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $filters['per_page'] = max(
            1,
            min((int) ($filters['per_page'] ?? 15), 100)
        );

        $customers = $this->userService->getCustomers($filters);

        return ApiResponse::success([
            'items' => UserResource::collection($customers->items()),

            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
        ], 'Customers retrieved successfully.');
    }

    public function show(string $uuid): JsonResponse
    {
        $customer = $this->userService->getCustomer($uuid);

        return ApiResponse::success(
            new UserResource($customer),
            'Customer retrieved successfully.'
        );
    }

    public function update(
        UpdateUserRequest $request,
        string $uuid
    ): JsonResponse {
        $customer = $this->userService->updateCustomer(
            $uuid,
            $request->validated()
        );

        return ApiResponse::success(
            new UserResource($customer),
            'Customer updated successfully.'
        );
    }

    public function updateStatus(
        UpdateCustomerStatusRequest $request,
        string $uuid
    ): JsonResponse {
        $customer = $this->userService->updateCustomerStatus(
            $uuid,
            (bool) $request->validated('is_active')
        );

        return ApiResponse::success(
            new UserResource($customer),
            $customer->is_active
                ? 'Customer activated successfully.'
                : 'Customer deactivated successfully.'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->userService->deleteCustomer($uuid);

        return ApiResponse::success(
            null,
            'Customer deleted successfully.'
        );
    }

    public function orders(
        ListCustomerOrderRequest $request,
        string $uuid
    ): JsonResponse {
        $filters = $request->validated();

        $filters['per_page'] = max(
            1,
            min((int) ($filters['per_page'] ?? 15), 100)
        );

        $orders = $this->userService->getCustomerOrders(
            $uuid,
            $filters
        );

        return ApiResponse::success([
            'items' => OrderResource::collection($orders->items()),

            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
        ], 'Customer orders retrieved successfully.');
    }
}