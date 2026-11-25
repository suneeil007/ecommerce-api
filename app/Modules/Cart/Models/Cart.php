<?php

namespace App\Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'status',
        'currency',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cart $cart) {
            if (empty($cart->uuid)) {
                $cart->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Find cart by UUID.
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Cart belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cart has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    /**
     * Scope active carts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get cart subtotal.
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum('total_price');
    }

    /**
     * Get total item quantity.
     */
    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }
}

