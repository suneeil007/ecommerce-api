<?php

namespace App\Modules\Attribute\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasFactory;

    protected $table = 'attributes';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'display_name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
        static::creating(function (Attribute $attribute) {
            if (empty($attribute->uuid)) {
                $attribute->uuid = (string) Str::uuid();
            }

            if (empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });

        static::updating(function (Attribute $attribute) {
            if ($attribute->isDirty('name')) {
                $attribute->slug = Str::slug($attribute->name);
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
    | Attribute Values
    |--------------------------------------------------------------------------
    */

    public function values(): HasMany
    {
        return $this->hasMany(
            AttributeValue::class,
            'attribute_id'
        )->orderBy('sort_order');
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
            'attribute_id',
            'product_id'
        )->withTimestamps();
    }
}

