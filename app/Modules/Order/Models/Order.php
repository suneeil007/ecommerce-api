<?php

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Location\Models\Province;
use App\Modules\Location\Models\District;
use App\Modules\Payment\Models\Payment;
use App\Modules\Order\Enums\FulfillmentStatus;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        // Identity
        'uuid',

        // Customer / Cart
        'user_id',
        'cart_id',

        // Customer Information
        'customer_name',
        'phone',
        'email',

        // Delivery Information
        'province_id',
        'province',
        'district_id',
        'district',
        'city',
        'address',

        // Payment
        'payment_method',

        // Order Information
        'order_number',
        'status',
        'payment_status',
        'fulfillment_status',

        // Financial Information
        'currency',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total_amount',

        // Additional Information
        'notes',
        'placed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',

        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'fulfillment_status' => FulfillmentStatus::class,

        'placed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Find By UUID
    |--------------------------------------------------------------------------
    */

    public static function findByUuid(string $uuid): ?self
    {
        return self::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function districtRecord(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->latest();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class)
            ->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForUser(
        Builder $query,
        int $userId
    ): Builder {
        return $query->where('user_id', $userId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getItemCountAttribute(): int
    {
        return $this->items->count();
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
