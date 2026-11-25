<?php

namespace App\Modules\Product\Repositories;

use App\Modules\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Modules\Product\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */

    /**
     * Get active products.
     */
    public function getActiveProducts(
        int $perPage = 15
    ): LengthAwarePaginator {

        return Product::query()
            ->where('is_active', true)
            ->where('status', 'published')
            ->with([
                'category',
                'primaryImage',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Find active product by slug.
     */
    public function findBySlug(
        string $slug
    ): ?Product {

        return Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('status', 'published')
            ->with([
                'category',
                'images',
                'attributeValues.attribute',
                'options.values',
                'variants.optionValues',
            ])
            ->first();
    }

    /**
     * Get featured products.
     */
    public function getFeaturedProducts(
        int $perPage = 15
    ): LengthAwarePaginator {

        return Product::query()
            ->where('is_active', true)
            ->where('status', 'published')
            ->where('is_featured', true)
            ->with([
                'category',
                'primaryImage',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    /**
     * Get products for admin listing.
     */
    public function getAdminProducts(
        int $perPage = 15
    ): LengthAwarePaginator {

        return Product::query()
            ->with([
                'category',
                'primaryImage',
                'variants',
            ])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find product by UUID.
     */
    public function findByUuid(
        string $uuid
    ): ?Product {

        return Product::query()
            ->where('uuid', $uuid)
            ->with([
                'category',
                'images',
                'attributeValues.attribute',
                'options.values',
                'variants.optionValues',
            ])
            ->first();
    }

    /**
     * Create product.
     */
    public function create(
        array $data
    ): Product {

        return Product::query()->create($data);
    }

    /**
     * Update product.
     */
    public function update(
        Product $product,
        array $data
    ): Product {

        $product->update($data);

        return $product->fresh();
    }

    /**
     * Delete product.
     */
    public function delete(
        Product $product
    ): bool {

        return (bool) $product->delete();
    }
}