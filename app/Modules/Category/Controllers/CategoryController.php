<?php

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Requests\StoreCategoryRequest;
use App\Modules\Category\Requests\UpdateCategoryRequest;
use App\Modules\Category\Resources\CategoryResource;
use App\Modules\Category\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | FRONTEND / CUSTOMER
    |--------------------------------------------------------------------------
    */


    /**
     * Get active root categories.
     *
     * GET /api/categories
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService
            ->getCategories();

        return ApiResponse::success(
            CategoryResource::collection($categories),
            'Categories retrieved successfully.'
        );
    }


    /**
     * Get category by slug.
     *
     * GET /api/categories/{slug}
     *
     * Example:
     *
     * /api/categories/electronics
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $category = $this->categoryService
            ->getBySlug($slug);

        if (!$category) {
            return ApiResponse::error(
                'Category not found.',
                404
            );
        }

        return ApiResponse::success(
            new CategoryResource($category),
            'Category retrieved successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */


    /**
     * Get paginated categories.
     *
     * GET /api/admin/categories
     *
     * Permission:
     * categories.view
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input(
            'per_page',
            15
        );

        /*
        |--------------------------------------------------------------------------
        | Pagination Limit
        |--------------------------------------------------------------------------
        */

        $perPage = max(
            1,
            min($perPage, 100)
        );


        $categories = $this->categoryService
            ->adminIndex($perPage);


        return ApiResponse::success(
            [
                'items' => CategoryResource::collection(
                    $categories->items()
                ),

                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
                ],
            ],
            'Categories retrieved successfully.'
        );
    }


    /**
     * Create category.
     *
     * POST /api/admin/categories
     *
     * Permission:
     * categories.create
     */
    public function store(
        StoreCategoryRequest $request
    ): JsonResponse {
        $category = $this->categoryService->create(
            $request->validated()
        );


        return ApiResponse::success(
            new CategoryResource($category),
            'Category created successfully.',
            201
        );
    }


    /**
     * Show category by UUID.
     *
     * GET /api/admin/categories/{uuid}
     *
     * Permission:
     * categories.view
     */
    public function adminShow(
        string $uuid
    ): JsonResponse {
        $category = $this->categoryService
            ->getByUuid($uuid);


        if (!$category) {
            return ApiResponse::error(
                'Category not found.',
                404
            );
        }


        return ApiResponse::success(
            new CategoryResource($category),
            'Category retrieved successfully.'
        );
    }


    /**
     * Update category by UUID.
     *
     * PUT /api/admin/categories/{uuid}
     *
     * Permission:
     * categories.update
     */
    public function update(
        UpdateCategoryRequest $request,
        string $uuid
    ): JsonResponse {

   
        $category = $this->categoryService
            ->getByUuid($uuid);


        if (!$category) {
            return ApiResponse::error(
                'Category not found.',
                404
            );
        }


        $category = $this->categoryService->update(
            $category,
            $request->validated()
        );


        return ApiResponse::success(
            new CategoryResource($category),
            'Category updated successfully.'
        );
    }


    /**
     * Delete category by UUID.
     *
     * DELETE /api/admin/categories/{uuid}
     *
     * Permission:
     * categories.delete
     */
    public function destroy(
        string $uuid
    ): JsonResponse {
        $category = $this->categoryService
            ->getByUuid($uuid);


        if (!$category) {
            return ApiResponse::error(
                'Category not found.',
                404
            );
        }


        $this->categoryService->delete(
            $category
        );


        return ApiResponse::success(
            null,
            'Category deleted successfully.'
        );
    }
}

