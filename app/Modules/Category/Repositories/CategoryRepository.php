<?php

namespace App\Modules\Category\Repositories;

use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Get all categories.
     *
     * Admin usage.
     */
    public function all(): Collection
    {
        return Category::query()
            ->with([
                'parent',
                'children',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }


    /**
     * Get paginated categories.
     *
     * Admin usage.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with([
                'parent',
                'children',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }


    /**
     * Find category by UUID.
     *
     * Used by admin.
     *
     * Example:
     *
     * /api/admin/categories/{uuid}
     */
    public function findByUuid(string $uuid): ?Category
    {
        return Category::query()
            ->with([
                'parent',
                'children',
            ])
            ->where('uuid', $uuid)
            ->first();
    }


    /**
     * Find category by slug.
     *
     * Used by frontend.
     *
     * Example:
     *
     * /api/categories/electronics
     */
    public function findBySlug(string $slug): ?Category
    {
        return Category::query()
            ->with([
                'parent',
                'children',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }


    /**
     * Create category.
     *
     * UUID and slug are generated
     * automatically by the Category model.
     */
    public function create(array $data): Category
    {
        $category = Category::create($data);

        return $category->fresh([
            'parent',
            'children',
        ]);
    }


    /**
     * Update category.
     *
     * UUID remains unchanged.
     *
     * If name changes, the Category model
     * automatically regenerates the slug.
     */
    public function update(
        Category $category,
        array $data
    ): Category {
        $category->update($data);

        return $category->fresh([
            'parent',
            'children',
        ]);
    }


    /**
     * Delete category.
     */
    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }


    /**
     * Get active root categories.
     *
     * Root category means:
     *
     * parent_id = NULL
     */
    public function getRootCategories(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }


    /**
     * Get active children of a category.
     */
    public function getChildren(Category $category): Collection
    {
        return $category->children()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}

