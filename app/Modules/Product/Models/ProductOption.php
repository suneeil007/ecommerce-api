<?php

namespace App\Modules\Product\Models;

use App\Modules\Attribute\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductOption extends Model
{
    use HasFactory;

    protected $table = 'product_options';

    protected $fillable = [
        'product_id',
        'attribute_id',
        'name',
        'display_name',
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
        static::creating(function (ProductOption $option) {

            if (empty($option->uuid)) {
                $option->uuid = (string) Str::uuid();
            }

            if (empty($option->slug)) {
                $option->slug = Str::slug(
                    $option->name
                );
            }
        });

        static::updating(function (ProductOption $option) {

            if ($option->isDirty('name')) {
                $option->slug = Str::slug(
                    $option->name
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
    | Product
    |--------------------------------------------------------------------------
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Attribute
    |--------------------------------------------------------------------------
    */

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class,
            'attribute_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Option Values
    |--------------------------------------------------------------------------
    */

    public function values(): HasMany
    {
        return $this->hasMany(
            ProductOptionValue::class,
            'product_option_id'
        )
            ->orderBy('sort_order')
            ->orderBy('value');
    }
}