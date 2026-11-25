<?php

namespace App\Modules\Category\Repositories\Contracts;

use App\Modules\Category\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Get all categories.
     */
    public function all(): Collection;

    /**
     * Get paginated categories.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find category by UUID.
     *
     * Used by admin.
     */
    public function findByUuid(string $uuid): ?Category;

    /**
     * Find category by slug.
     *
     * Used by frontend.
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Create category.
     */
    public function create(array $data): Category;

    /**
     * Update category.
     */
    public function update(
        Category $category,
        array $data
    ): Category;

    /**
     * Delete category.
     */
    public function delete(Category $category): bool;

    /**
     * Get active root categories.
     */
    public function getRootCategories(): Collection;

    /**
     * Get active child categories.
     */
    public function getChildren(Category $category): Collection;
}

