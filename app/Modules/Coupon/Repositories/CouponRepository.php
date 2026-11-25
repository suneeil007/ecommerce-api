<?php

namespace App\Modules\Coupon\Repositories;

use App\Modules\Coupon\Models\Coupon;
use App\Modules\Coupon\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CouponRepository implements CouponRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        return $this->query($filters)
            ->latest()
            ->get();
    }

    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {
        return $this->query($filters)
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(
        string $uuid
    ): ?Coupon {
        return Coupon::query()
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByCode(
        string $code
    ): ?Coupon {
        return Coupon::query()
            ->whereRaw(
                'UPPER(code) = ?',
                [strtoupper(trim($code))]
            )
            ->first();
    }

    public function create(array $data): Coupon
    {
        return Coupon::create($data);
    }

    public function update(
        Coupon $coupon,
        array $data
    ): Coupon {
        $coupon->update($data);

        return $coupon->fresh();
    }

    public function delete(Coupon $coupon): bool
    {
        return (bool) $coupon->delete();
    }

    public function incrementUsage(
        Coupon $coupon
    ): bool {
        return $coupon->increment('used_count');
    }

    private function query(array $filters)
    {
        $query = Coupon::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere(
                        'name',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (
            array_key_exists(
                'is_active',
                $filters
            )
        ) {
            $query->where(
                'is_active',
                (bool) $filters['is_active']
            );
        }

        if (!empty($filters['discount_type'])) {
            $query->where(
                'discount_type',
                $filters['discount_type']
            );
        }

        return $query;
    }
}