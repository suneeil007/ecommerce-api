<?php

namespace App\Modules\Attribute\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class AttributeValue extends Model
{
    use HasFactory;

    protected $table = 'attribute_values';

    protected $fillable = [
        'uuid',
        'attribute_id',
        'value',
        'slug',
        'display_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'attribute_id' => 'integer',
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
        static::creating(function (AttributeValue $value) {
            if (empty($value->uuid)) {
                $value->uuid = (string) Str::uuid();
            }

            if (empty($value->slug)) {
                $value->slug = Str::slug($value->value);
            }
        });

        static::updating(function (AttributeValue $value) {
            if ($value->isDirty('value')) {
                $value->slug = Str::slug($value->value);
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
    | UUID Lookup
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
    | Products
    |--------------------------------------------------------------------------
    */

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_attribute_values',
            'attribute_value_id',
            'product_id'
        )->withTimestamps();
    }
}

