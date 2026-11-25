<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantOptionValue extends Model
{
    use HasFactory;

    protected $table = 'product_variant_option_values';

    protected $fillable = [
        'product_variant_id',
        'product_option_value_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Product Variant
    |--------------------------------------------------------------------------
    */

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Product Option Value
    |--------------------------------------------------------------------------
    */

    public function productOptionValue(): BelongsTo
    {
        return $this->belongsTo(
            ProductOptionValue::class,
            'product_option_value_id'
        );
    }
}