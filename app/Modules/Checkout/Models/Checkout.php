<?php

namespace App\Modules\Checkout\Models;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Checkout\Enums\CheckoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Checkout extends Model
{
    protected $table = 'checkouts';

    protected $fillable = [
        'uuid',
        'user_id',
        'cart_id',
        'status',
        'currency',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total_amount',
        'coupon_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CheckoutStatus::class,

            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Checkout $checkout) {
            if (empty($checkout->uuid)) {
                $checkout->uuid = (string) Str::uuid();
            }
        });
    }

    public static function findByUuid(string $uuid): ?self
    {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CheckoutItem::class);
    }
}

