<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasFactory;

    protected $table = 'attributes';

    protected $fillable = [
        'name',
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
                $attribute->slug = Str::slug(
                    $attribute->name
                );
            }
        });

        static::updating(function (Attribute $attribute) {

            if ($attribute->isDirty('name')) {
                $attribute->slug = Str::slug(
                    $attribute->name
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
    | Attribute Values
    |--------------------------------------------------------------------------
    */

    public function values(): HasMany
    {
        return $this->hasMany(
            AttributeValue::class,
            'attribute_id'
        )
            ->orderBy('sort_order')
            ->orderBy('value');
    }
}