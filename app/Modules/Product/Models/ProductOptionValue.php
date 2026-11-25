<?php

namespace App\Modules\Product\Models;

use App\Modules\Attribute\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductOptionValue extends Model
{
    use HasFactory;

    protected $table = 'product_option_values';

    protected $fillable = [
        'product_option_id',
        'attribute_value_id',
        'value',
        'display_value',
        'color_code',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (ProductOptionValue $value) {

            if (empty($value->uuid)) {
                $value->uuid = (string) Str::uuid();
            }

            if (empty($value->slug)) {
                $value->slug = Str::slug(
                    $value->value
                );
            }
        });

        static::updating(function (ProductOptionValue $value) {

            if ($value->isDirty('value')) {
                $value->slug = Str::slug(
                    $value->value
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Route Model Binding
    |--------------------------------------------------------------------------
    */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | Admin UUID Lookup
    |--------------------------------------------------------------------------
    */

    public static function findByUuid(string $uuid): ?self
    {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Product Option
    |--------------------------------------------------------------------------
    */

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(
            ProductOption::class,
            'product_option_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Attribute Value
    |--------------------------------------------------------------------------
    */

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(
            AttributeValue::class,
            'attribute_value_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Variants
    |--------------------------------------------------------------------------
    */

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_option_values',
            'product_option_value_id',
            'product_variant_id'
        )->withTimestamps();
    }
}