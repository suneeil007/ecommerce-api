<?php

namespace App\Modules\Order\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OrderController
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Create Guest / Authenticated Order
    |--------------------------------------------------------------------------
    |
    | POST /api/orders
    |
    | Supports guest and authenticated checkout.
    |
    */

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = Auth::guard('sanctum')->user();

            $data = $request->validatedData();

            $order = $this->orderService->createFromCheckoutData(
                $data,
                $user
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order created successfully.',
                201
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                400
            );
        } catch (\Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to create order.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Orders
    |--------------------------------------------------------------------------
    |
    | GET /api/orders
    |
    */

    public function index(
        Request $request
    ): JsonResponse {
        try {
            /** @var User|null $user */
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    401
                );
            }

            $orders = $this->orderService->getUserOrders(
                $user
            );

            return ApiResponse::success(
                OrderResource::collection($orders),
                'Orders retrieved successfully.'
            );
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve orders.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Order Detail
    |--------------------------------------------------------------------------
    |
    | GET /api/orders/{uuid}
    |
    */

    public function show(
        Request $request,
        string $uuid
    ): JsonResponse {
        try {
            /** @var User|null $user */
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    401
                );
            }

            $order = $this->orderService->getUserOrder(
                $user,
                $uuid
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order retrieved successfully.'
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve order.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order From Authenticated Checkout Session
    |--------------------------------------------------------------------------
    |
    | POST /api/orders/from-checkout/{checkoutUuid}
    |
    */

    public function storeFromCheckout(
        Request $request,
        string $checkoutUuid
    ): JsonResponse {
        try {
            /** @var User|null $user */
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    401
                );
            }

            $order = $this->orderService->createFromCheckout(
                $user,
                $checkoutUuid
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order created from checkout successfully.',
                201
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to create order from checkout.',
                500
            );
        }
    }
}