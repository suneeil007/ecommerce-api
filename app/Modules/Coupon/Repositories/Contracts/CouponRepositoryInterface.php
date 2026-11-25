<?php

namespace App\Modules\Coupon\Repositories\Contracts;

use App\Modules\Coupon\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CouponRepositoryInterface
{
    public function all(array $filters = []): Collection;

    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?Coupon;

    public function findByCode(string $code): ?Coupon;

    public function create(array $data): Coupon;

    public function update(
        Coupon $coupon,
        array $data
    ): Coupon;

    public function delete(Coupon $coupon): bool;

    public function incrementUsage(
        Coupon $coupon
    ): bool;
}