<?php

namespace App\Modules\Product\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'uuid',
        'name',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'cost_price',
        'stock',
        'low_stock_threshold',
        'weight',
        'image',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:3',

            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'sort_order' => 'integer',

            'is_active' => 'boolean',
        ];
    }

    /**
     * Generate UUID automatically.
     */
    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant) {
            if (empty($variant->uuid)) {
                $variant->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Find variant by UUID.
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Product relationship.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    /**
     * Option values belonging to this variant.
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
            'product_variant_id',
            'product_option_value_id'
        )->withTimestamps();
    }
}

