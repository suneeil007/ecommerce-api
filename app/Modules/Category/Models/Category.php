<?php

namespace App\Modules\Category\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'name',
        'display_name',
        'description',
        'image',
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
        /*
        |--------------------------------------------------------------------------
        | Creating
        |--------------------------------------------------------------------------
        */

        static::creating(function (Category $category) {

            /*
             * Generate UUID automatically.
             *
             * Used by admin URLs.
             */
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid();
            }

            /*
             * Generate slug from category name.
             *
             * Example:
             *
             * Electronics
             *     ↓
             * electronics
             */
            if (empty($category->slug)) {
                $category->slug = Str::slug(
                    $category->name
                );
            }
        });


        /*
        |--------------------------------------------------------------------------
        | Updating
        |--------------------------------------------------------------------------
        */

        static::updating(function (Category $category) {

            /*
             * If category name changes,
             * automatically regenerate slug.
             */
            if ($category->isDirty('name')) {

                $category->slug = Str::slug(
                    $category->name
                );
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
     * /api/categories/electronics
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

    /**
     * Find category by UUID.
     *
     * Admin routes use UUID.
     */
    public static function findByUuid(
        string $uuid
    ): ?self {
        return static::query()
            ->where('uuid', $uuid)
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Parent Category
    |--------------------------------------------------------------------------
    */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Child Categories
    |--------------------------------------------------------------------------
    */

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        )
            ->orderBy('sort_order')
            ->orderBy('name');
    }


    /*
    |--------------------------------------------------------------------------
    | Recursive Children
    |--------------------------------------------------------------------------
    */

    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with('childrenRecursive');
    }
}

