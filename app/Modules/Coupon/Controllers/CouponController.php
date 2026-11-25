<?php

namespace App\Modules\Coupon\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\Coupon\Requests\ApplyCouponRequest;
use App\Modules\Coupon\Requests\StoreCouponRequest;
use App\Modules\Coupon\Requests\UpdateCouponRequest;
use App\Modules\Coupon\Resources\CouponResource;
use App\Modules\Coupon\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */


    /**
     * Get paginated coupons.
     *
     * GET /api/admin/coupons
     *
     * Permission:
     * coupons.view
     */
    public function adminIndex(
        Request $request
    ): JsonResponse {
        $perPage = (int) $request->input(
            'per_page',
            15
        );

        /*
        |--------------------------------------------------------------------------
        | Pagination Limit
        |--------------------------------------------------------------------------
        */

        $perPage = max(
            1,
            min($perPage, 100)
        );


        $filters = $request->only([
            'search',
            'is_active',
            'discount_type',
        ]);


        $coupons = $this->couponService->list(
            $filters,
            $perPage
        );


        return ApiResponse::success(
            [
                'items' => CouponResource::collection(
                    $coupons->items()
                ),

                'pagination' => [
                    'current_page' =>
                        $coupons->currentPage(),

                    'last_page' =>
                        $coupons->lastPage(),

                    'per_page' =>
                        $coupons->perPage(),

                    'total' =>
                        $coupons->total(),

                    'from' =>
                        $coupons->firstItem(),

                    'to' =>
                        $coupons->lastItem(),
                ],
            ],
            'Coupons retrieved successfully.'
        );
    }


    /**
     * Create coupon.
     *
     * POST /api/admin/coupons
     *
     * Permission:
     * coupons.create
     */
    public function store(
        StoreCouponRequest $request
    ): JsonResponse {
        $coupon = $this->couponService->create(
            $request->validated()
        );


        return ApiResponse::success(
            new CouponResource($coupon),
            'Coupon created successfully.',
            201
        );
    }


    /**
     * Show coupon by UUID.
     *
     * GET /api/admin/coupons/{uuid}
     *
     * Permission:
     * coupons.view
     */
    public function adminShow(
        string $uuid
    ): JsonResponse {
        $coupon = $this->couponService
            ->getByUuid($uuid);


        if (!$coupon) {
            return ApiResponse::error(
                'Coupon not found.',
                404
            );
        }


        return ApiResponse::success(
            new CouponResource($coupon),
            'Coupon retrieved successfully.'
        );
    }


    /**
     * Update coupon by UUID.
     *
     * PUT /api/admin/coupons/{uuid}
     *
     * Permission:
     * coupons.update
     */
    public function update(
        UpdateCouponRequest $request,
        string $uuid
    ): JsonResponse {
        $coupon = $this->couponService
            ->getByUuid($uuid);


        if (!$coupon) {
            return ApiResponse::error(
                'Coupon not found.',
                404
            );
        }


        $coupon = $this->couponService->update(
            $coupon,
            $request->validated()
        );


        return ApiResponse::success(
            new CouponResource($coupon),
            'Coupon updated successfully.'
        );
    }


    /**
     * Delete coupon by UUID.
     *
     * DELETE /api/admin/coupons/{uuid}
     *
     * Permission:
     * coupons.delete
     */
    public function destroy(
        string $uuid
    ): JsonResponse {
        $coupon = $this->couponService
            ->getByUuid($uuid);


        if (!$coupon) {
            return ApiResponse::error(
                'Coupon not found.',
                404
            );
        }


        $this->couponService->delete(
            $coupon
        );


        return ApiResponse::success(
            null,
            'Coupon deleted successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER
    |--------------------------------------------------------------------------
    */

 
    /**
     * Apply coupon.
     *
     * POST /api/coupons/apply
     *
     * Supports authenticated and guest customers.
     */
    public function apply(
        ApplyCouponRequest $request
    ): JsonResponse {
        $userId = auth('sanctum')->id();

        try {
            $result = $this->couponService->validate(
                $request->validated('code'),
                (float) $request->validated('cart_total'),
                $userId
            );

            return ApiResponse::success(
                [
                    'coupon' => new CouponResource(
                        $result['coupon']
                    ),
                    'discount' => number_format(
                        $result['discount'],
                        2,
                        '.',
                        ''
                    ),
                    'payable_amount' => number_format(
                        $result['payable_amount'],
                        2,
                        '.',
                        ''
                    ),
                    'free_shipping' => $result['free_shipping'],
                ],
                'Coupon applied successfully.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }
    }


    
}

