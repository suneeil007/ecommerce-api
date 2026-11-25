<?php

namespace App\Modules\Cart\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'uuid',
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'cart_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CartItem $item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Find cart item by UUID.
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Cart item belongs to cart.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Cart item belongs to product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Cart item belongs to product variant.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }
}

