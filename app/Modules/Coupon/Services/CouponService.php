<?php

namespace App\Modules\Coupon\Services;

use App\Modules\Coupon\Models\Coupon;
use App\Modules\Coupon\Models\CouponUsage;
use App\Modules\Coupon\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function __construct(
        protected CouponRepositoryInterface $repository
    ) {
    }

    /**
     * Get paginated coupons.
     */
    public function list(
        array $filters = [],
        int $perPage = 15
    ) {
        return $this->repository->paginate(
            $perPage,
            $filters
        );
    }

    /**
     * Create a coupon.
     */
    public function create(array $data): Coupon
    {
        $data['code'] = strtoupper(
            trim($data['code'])
        );

        $data['is_active'] =
            $data['is_active'] ?? true;

        $data['minimum_order_amount'] =
            $data['minimum_order_amount'] ?? 0;

        /*
         * Never accept used_count from the request.
         * A newly created coupon always starts at zero.
         */
        $data['used_count'] = 0;

        return $this->repository->create($data);
    }

    /**
     * Update a coupon.
     */
    public function update(
        Coupon $coupon,
        array $data
    ): Coupon {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(
                trim($data['code'])
            );
        }

        /*
         * used_count is system-managed.
         */
        unset($data['used_count']);

        return $this->repository->update(
            $coupon,
            $data
        );
    }

    /**
     * Delete a coupon.
     */
    public function delete(Coupon $coupon): bool
    {
        return $this->repository->delete($coupon);
    }

    /**
     * Find a coupon by UUID.
     */
    public function getByUuid(string $uuid): ?Coupon
    {
        return $this->repository->findByUuid($uuid);
    }

    /**
     * Validate and calculate a coupon.
     */
    public function validate(
        string $code,
        float $cartTotal,
        ?int $userId = null
    ): array {
        $coupon = $this->repository->findByCode($code);

        if (!$coupon) {
            throw ValidationException::withMessages([
                'code' => 'Invalid coupon code.',
            ]);
        }

        $this->validateCoupon(
            $coupon,
            $cartTotal,
            $userId
        );

        $discount = $this->calculateDiscount(
            $coupon,
            $cartTotal
        );

        return [
            'coupon' => $coupon,
            'discount' => $discount,
            'payable_amount' => max(
                0,
                $cartTotal - $discount
            ),
            'free_shipping' => $coupon->is_free_shipping,
        ];
    }

    /**
     * Calculate coupon discount.
     */
    public function calculateDiscount(
        Coupon $coupon,
        float $cartTotal
    ): float {
        if ($coupon->discount_type === 'fixed') {
            return min(
                (float) $coupon->discount_value,
                $cartTotal
            );
        }

        $discount = (
            $cartTotal *
            (float) $coupon->discount_value
        ) / 100;

        if (
            $coupon->maximum_discount_amount !== null
        ) {
            $discount = min(
                $discount,
                (float) $coupon->maximum_discount_amount
            );
        }

        return min(
            $discount,
            $cartTotal
        );
    }

    /**
     * Record successful coupon usage.
     *
     * This should be called only after
     * the order has been successfully created.
     */
    public function recordUsage(
        Coupon $coupon,
        ?int $userId,
        ?int $orderId,
        float $discountAmount,
        ?string $guestToken = null
    ): CouponUsage {
        return DB::transaction(
            function () use (
                $coupon,
                $userId,
                $orderId,
                $discountAmount,
                $guestToken
            ) {
                $usage = CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $userId,
                    'guest_token' => $guestToken,
                    'order_id' => $orderId,
                    'discount_amount' => $discountAmount,
                ]);

                $this->repository->incrementUsage(
                    $coupon
                );

                return $usage;
            }
        );
    }

    /**
     * Validate coupon rules.
     */
    private function validateCoupon(
        Coupon $coupon,
        float $cartTotal,
        ?int $userId = null
    ): void {
        if (!$coupon->is_active) {
            throw ValidationException::withMessages([
                'code' => 'This coupon is inactive.',
            ]);
        }

        if (
            $coupon->starts_at &&
            now()->lt($coupon->starts_at)
        ) {
            throw ValidationException::withMessages([
                'code' => 'This coupon is not active yet.',
            ]);
        }

        if (
            $coupon->expires_at &&
            now()->gt($coupon->expires_at)
        ) {
            throw ValidationException::withMessages([
                'code' => 'This coupon has expired.',
            ]);
        }

        if (
            $coupon->usage_limit !== null &&
            $coupon->used_count >= $coupon->usage_limit
        ) {
            throw ValidationException::withMessages([
                'code' => 'This coupon has reached its usage limit.',
            ]);
        }

        if (
            $cartTotal <
            (float) $coupon->minimum_order_amount
        ) {
            throw ValidationException::withMessages([
                'cart_total' =>
                    'Minimum order amount for this coupon is ' .
                    number_format(
                        (float) $coupon->minimum_order_amount,
                        2
                    ) . '.',
            ]);
        }

        /*
         * Per-user usage limit.
         */
        if (
            $userId !== null &&
            $coupon->usage_limit_per_user !== null
        ) {
            $userUsageCount = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->count();

            if (
                $userUsageCount >=
                $coupon->usage_limit_per_user
            ) {
                throw ValidationException::withMessages([
                    'code' =>
                        'You have already reached the usage limit for this coupon.',
                ]);
            }
        }

        /*
         * NOTE:
         *
         * is_first_order_only should eventually be checked
         * against the Order module's actual order history.
         *
         * It should NOT be based only on CouponUsage because
         * a customer could have an order without using a coupon.
         */
        if (
            $coupon->is_first_order_only &&
            $userId !== null
        ) {
            /*
             * This temporary check can be replaced once
             * the Order module is connected.
             */
            $hasUsedCouponBefore = CouponUsage::query()
                ->where('user_id', $userId)
                ->exists();

            if ($hasUsedCouponBefore) {
                throw ValidationException::withMessages([
                    'code' =>
                        'This coupon is available for first-time use only.',
                ]);
            }
        }
    }
}

