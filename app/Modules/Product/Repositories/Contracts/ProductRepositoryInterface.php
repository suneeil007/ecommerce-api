<?php

namespace App\Modules\Product\Repositories\Contracts;

use App\Modules\Product\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
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
    ): LengthAwarePaginator;

    /**
     * Find active product by slug.
     */
    public function findBySlug(
        string $slug
    ): ?Product;

    /**
     * Get featured products.
     */
    public function getFeaturedProducts(
        int $perPage = 15
    ): LengthAwarePaginator;

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
    ): LengthAwarePaginator;

    /**
     * Find product by UUID.
     */
    public function findByUuid(
        string $uuid
    ): ?Product;

    /**
     * Create product.
     */
    public function create(
        array $data
    ): Product;

    /**
     * Update product.
     */
    public function update(
        Product $product,
        array $data
    ): Product;

    /**
     * Delete product.
     */
    public function delete(
        Product $product
    ): bool;
}