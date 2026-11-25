<?php

namespace App\Modules\Coupon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'maximum_discount_amount',
        'minimum_order_amount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'is_first_order_only',
        'is_free_shipping',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_first_order_only' => 'boolean',
        'is_free_shipping' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Coupon $coupon) {
            if (empty($coupon->uuid)) {
                $coupon->uuid = (string) Str::uuid();
            }

            $coupon->code = strtoupper(
                trim($coupon->code)
            );
        });

        static::updating(function (Coupon $coupon) {
            if ($coupon->isDirty('code')) {
                $coupon->code = strtoupper(
                    trim($coupon->code)
                );
            }
        });
    }

    public function usages(): HasMany
    {
        return $this->hasMany(
            CouponUsage::class,
            'coupon_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (
            $this->starts_at &&
            now()->lt($this->starts_at)
        ) {
            return false;
        }

        if (
            $this->expires_at &&
            now()->gt($this->expires_at)
        ) {
            return false;
        }

        if (
            $this->usage_limit !== null &&
            $this->used_count >= $this->usage_limit
        ) {
            return false;
        }

        return true;
    }
}