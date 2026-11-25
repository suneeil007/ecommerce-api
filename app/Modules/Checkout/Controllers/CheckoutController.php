<?php

namespace App\Modules\Checkout\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use App\Modules\Checkout\Requests\StoreCheckoutRequest;
use App\Modules\Checkout\Resources\CheckoutResource;
use App\Modules\Checkout\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class CheckoutController
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {
    }

    /**
     * Create a checkout from the authenticated user's active cart.
     */
    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    401
                );
            }

            $data = $request->validatedData();

            $checkout = $this->checkoutService->createFromCart(
                $user,
                $data['notes'] ?? null
            );

            return ApiResponse::success(
                new CheckoutResource($checkout),
                'Checkout created successfully.',
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
                'Unable to create checkout.',
                500
            );
        }
    }

    /**
     * Get authenticated user's checkouts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    401
                );
            }

            $checkouts = $this->checkoutService
                ->getUserCheckouts($user);

            return ApiResponse::success(
                CheckoutResource::collection($checkouts),
                'Checkouts retrieved successfully.'
            );
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve checkouts.',
                500
            );
        }
    }

    /**
     * Get one checkout belonging to the authenticated user.
     */
    public function show(
        Request $request,
        string $uuid
    ): JsonResponse {
        try {
            /** @var User $user */
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    401
                );
            }

            $checkout = $this->checkoutService
                ->getUserCheckout($user, $uuid);

            return ApiResponse::success(
                new CheckoutResource($checkout),
                'Checkout retrieved successfully.'
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve checkout.',
                500
            );
        }
    }
}

