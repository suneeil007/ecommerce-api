<?php

namespace App\Modules\Payment\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use App\Modules\Payment\Requests\StorePaymentRequest;
use App\Modules\Payment\Requests\ListPaymentRequest;
use App\Modules\Payment\Resources\PaymentResource;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class PaymentController
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePaymentRequest $request
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

            $data = $request->validatedData();

            $payment = $this->paymentService->create(
                user: $user,
                orderUuid: $data['order_uuid'],
                method: $data['method'],
                amount: $data['amount'],
                reference: $data['reference'] ?? null,
                notes: $data['notes'] ?? null
            );

            return ApiResponse::success(
                new PaymentResource($payment),
                'Payment created successfully.',
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
                'Unable to create payment.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | User Payments
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
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

            $payments = $this->paymentService
                ->getUserPayments($user);

            return ApiResponse::success(
                PaymentResource::collection($payments),
                'Payments retrieved successfully.'
            );

        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve payments.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | User Payment Details
    |--------------------------------------------------------------------------
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

            $payment = $this->paymentService
                ->getUserPayment(
                    $user,
                    $uuid
                );

            return ApiResponse::success(
                new PaymentResource($payment),
                'Payment retrieved successfully.'
            );

        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                404
            );

        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve payment.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - All Payments
    |--------------------------------------------------------------------------
    */

    
    /*
    |--------------------------------------------------------------------------
    | Admin - All Payments
    |--------------------------------------------------------------------------
    */

    public function adminIndex(
        ListPaymentRequest $request
    ): JsonResponse {
        try {
            $payments = $this->paymentService
                ->getAdminPayments(
                    $request->validated()
                );

            return ApiResponse::success([
                'items' => PaymentResource::collection(
                    $payments->getCollection()
                )->resolve($request),

                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                    'has_more_pages' => $payments->hasMorePages(),
                    'next_page_url' => $payments->nextPageUrl(),
                    'prev_page_url' => $payments->previousPageUrl(),
                ],
            ], 'Payments retrieved successfully.');

        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve payments.',
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Admin - Payment Statistics
    |--------------------------------------------------------------------------
    */

    public function adminStatistics(
        ListPaymentRequest $request
    ): JsonResponse {
        try {
            $statistics = $this->paymentService
                ->getAdminStatistics(
                    $request->safe()->only([
                        'from',
                        'to',
                    ])
                );

            return ApiResponse::success(
                $statistics,
                'Payment statistics retrieved successfully.'
            );

        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve payment statistics.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - Payment Details
    |--------------------------------------------------------------------------
    */

    public function adminShow(
        string $uuid
    ): JsonResponse {
        try {
            $payment = $this->paymentService
                ->getPaymentByUuid($uuid);

            return ApiResponse::success(
                new PaymentResource($payment),
                'Payment retrieved successfully.'
            );

        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                404
            );

        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to retrieve payment.',
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | eSewa - Initiate Payment
    |--------------------------------------------------------------------------
    */

    public function initiateEsewa(
        Request $request
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

            $request->validate([
                'order_uuid' => [
                    'required',
                    'uuid',
                ],
            ]);

            $result = $this->paymentService->initiateEsewa(
                user: $user,
                orderUuid: $request->input('order_uuid')
            );

            return ApiResponse::success(
                $result,
                'eSewa payment initiated successfully.'
            );

        } catch (RuntimeException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                400
            );

        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                'Unable to initiate eSewa payment.',
                500
            );
        }
    }
}
