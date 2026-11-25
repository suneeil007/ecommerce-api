<?php

namespace App\Modules\Product\Models;

use App\Modules\Category\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Fields
    |--------------------------------------------------------------------------
    |
    | These fields can be assigned using:
    |
    |     Product::create([...])
    |
    | or:
    |
    |     $product->update([...])
    |
    | IMPORTANT:
    |
    | product_type must be included here because ProductService updates it
    | when converting a simple product to a variable product.
    |
    */

    protected $fillable = [
        'uuid',

        'category_id',

        'name',
        'slug',
        'sku',
        'display_name',

        /*
        |--------------------------------------------------------------------------
        | Product Type
        |--------------------------------------------------------------------------
        |
        | Examples:
        |
        | simple
        | variable
        |
        */

        'product_type',

        'short_description',
        'description',

        'price',
        'compare_at_price',
        'cost_price',

        'stock',
        'low_stock_threshold',

        'weight',

        'status',

        'is_featured',
        'is_active',

        'sort_order',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            /*
            |------------------------------------------------------------------
            | Product Type
            |------------------------------------------------------------------
            */

            'product_type' => 'string',

            /*
            |------------------------------------------------------------------
            | Money
            |------------------------------------------------------------------
            */

            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',

            /*
            |------------------------------------------------------------------
            | Weight
            |------------------------------------------------------------------
            */

            'weight' => 'decimal:3',

            /*
            |------------------------------------------------------------------
            | Integer Fields
            |------------------------------------------------------------------
            */

            'stock' => 'integer',
            'low_stock_threshold' => 'integer',

            /*
            |------------------------------------------------------------------
            | Boolean Fields
            |------------------------------------------------------------------
            */

            'is_featured' => 'boolean',
            'is_active' => 'boolean',

            /*
            |------------------------------------------------------------------
            | Sorting
            |------------------------------------------------------------------
            */

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
        /*
        |--------------------------------------------------------------------------
        | Creating
        |--------------------------------------------------------------------------
        */

        static::creating(function (Product $product) {

            /*
            |------------------------------------------------------------------
            | Generate UUID
            |------------------------------------------------------------------
            */

            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }

            /*
            |------------------------------------------------------------------
            | Generate Slug
            |------------------------------------------------------------------
            */

            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Updating
        |--------------------------------------------------------------------------
        */

        static::updating(function (Product $product) {

            /*
            |------------------------------------------------------------------
            | Regenerate Slug When Name Changes
            |------------------------------------------------------------------
            */

            if ($product->isDirty('name')) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Route Model Binding
    |--------------------------------------------------------------------------
    */

    /**
     * Frontend routes use slug.
     *
     * Example:
     *
     * /api/products/iphone-15
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
    | Category
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'category_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    */

    public function images(): HasMany
    {
        return $this->hasMany(
            ProductImage::class,
            'product_id'
        )
            ->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Primary Image
    |--------------------------------------------------------------------------
    */

    public function primaryImage(): HasOne
    {
        return $this->hasOne(
            ProductImage::class,
            'product_id'
        )
            ->where('is_primary', true)
            ->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Product Attribute Values
    |--------------------------------------------------------------------------
    */

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(
            ProductAttributeValue::class,
            'product_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Attribute Values
    |--------------------------------------------------------------------------
    */

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_attribute_values',
            'product_id',
            'attribute_value_id'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Product Options
    |--------------------------------------------------------------------------
    */

    public function options(): HasMany
    {
        return $this->hasMany(
            ProductOption::class,
            'product_id'
        )
            ->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Product Variants
    |--------------------------------------------------------------------------
    */

    public function variants(): HasMany
    {
        return $this->hasMany(
            ProductVariant::class,
            'product_id'
        )
            ->orderBy('sort_order');
    }
}

