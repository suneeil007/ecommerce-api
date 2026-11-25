<?php

namespace App\Modules\Product\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use App\Modules\Product\Resources\ProductResource;
use App\Modules\Product\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | FRONTEND / CUSTOMER
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $products = $this->productService->getPublicProducts(
            $perPage
        );

        return ApiResponse::success(
            [
                'items' => ProductResource::collection(
                    $products->items()
                ),

                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ],
            'Products retrieved successfully.'
        );
    }

    public function featured(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $products = $this->productService->getFeaturedProducts(
            $perPage
        );

        return ApiResponse::success(
            [
                'items' => ProductResource::collection(
                    $products->items()
                ),

                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ],
            'Featured products retrieved successfully.'
        );
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->findBySlug($slug);

        if (!$product) {
            return ApiResponse::error(
                'Product not found.',
                404
            );
        }

        return ApiResponse::success(
            new ProductResource($product),
            'Product retrieved successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    public function adminIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $products = $this->productService->getAdminProducts(
            $perPage
        );

        return ApiResponse::success(
            [
                'items' => ProductResource::collection(
                    $products->items()
                ),

                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ],
            'Products retrieved successfully.'
        );
    }

    public function store(
        StoreProductRequest $request
    ): JsonResponse {
        $data = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Product Images
        |--------------------------------------------------------------------------
        |
        | StoreProductRequest validates "images".
        | ProductService expects the uploaded files in "images".a
        |
        */

        $data['images'] = $request->file(
            'images',
            []
        );

        $product = $this->productService->create(
            $data
        );

        return ApiResponse::success(
            new ProductResource($product),
            'Product created successfully.',
            201
        );
    }

    public function adminShow(
        string $uuid
    ): JsonResponse {
        $product = $this->productService->findByUuid(
            $uuid
        );

        if (!$product) {
            return ApiResponse::error(
                'Product not found.',
                404
            );
        }

        return ApiResponse::success(
            new ProductResource($product),
            'Product retrieved successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateProductRequest $request,
        string $uuid
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Find Product
        |--------------------------------------------------------------------------
        */

        $product = $this->productService->findByUuid(
            $uuid
        );

        if (!$product) {
            return ApiResponse::error(
                'Product not found.',
                404
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validated Data
        |--------------------------------------------------------------------------
        */

        $data = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Product Images
        |--------------------------------------------------------------------------
        |
        | New product-level images are sent as:
        |
        | images[]
        |
        | Only add the field when files were actually uploaded.
        |
        */

        if ($request->hasFile('images')) {
            $data['images'] = $request->file(
                'images'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Variant Images
        |--------------------------------------------------------------------------
        |
        | React sends variant data and variant image files separately.
        |
        | Example:
        |
        | variants:
        | [
        |     {
        |         "id": 22,
        |         "uuid": "6cf84d1e-...",
        |         ...
        |     }
        | ]
        |
        | variant_images[0]:
        |     apple.jpg
        |
        | variant_image_keys[0]:
        |     6cf84d1e-...
        |
        | The key tells us which variant owns the uploaded image.
        |
        */

        if (
            $request->hasFile('variant_images') &&
            !empty($data['variants'])
        ) {
            /*
            |--------------------------------------------------------------------------
            | Uploaded Variant Images
            |--------------------------------------------------------------------------
            */

            $variantImages = $request->file(
                'variant_images'
            );

            /*
            |--------------------------------------------------------------------------
            | Variant UUIDs
            |--------------------------------------------------------------------------
            */

            $variantImageKeys = $request->input(
                'variant_image_keys',
                []
            );

            /*
            |--------------------------------------------------------------------------
            | Build Variant Image Map
            |--------------------------------------------------------------------------
            |
            | Convert:
            |
            | variant_images[0] = apple.jpg
            | variant_image_keys[0] = UUID
            |
            | into:
            |
            | [
            |     UUID => UploadedFile
            | ]
            |
            */

            $variantImageMap = [];

            foreach (
                $variantImages as $index => $file
            ) {
                $variantUuid =
                    $variantImageKeys[$index]
                    ?? null;

                if (
                    $variantUuid &&
                    $file instanceof UploadedFile
                ) {
                    $variantImageMap[$variantUuid] =
                        $file;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Attach Uploaded Image To Matching Variant
            |--------------------------------------------------------------------------
            |
            | ProductService::syncVariants() expects:
            |
            | $variantData['image']
            |
            | Therefore, add the UploadedFile directly
            | to the matching variant.
            |
            */

            foreach (
                $data['variants'] as &$variant
            ) {
                $variantUuid =
                    $variant['uuid']
                    ?? null;

                if (
                    $variantUuid &&
                    isset(
                        $variantImageMap[$variantUuid]
                    )
                ) {
                    $variant['image'] =
                        $variantImageMap[$variantUuid];
                }
            }

            unset($variant);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Product Through Service
        |--------------------------------------------------------------------------
        */

        $product = $this->productService->update(
            $product,
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | Return Updated Product
        |--------------------------------------------------------------------------
        */

        return ApiResponse::success(
            new ProductResource($product),
            'Product updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(
        string $uuid
    ): JsonResponse {
        $product = $this->productService->findByUuid(
            $uuid
        );

        if (!$product) {
            return ApiResponse::error(
                'Product not found.',
                404
            );
        }

        $this->productService->delete(
            $product
        );

        return ApiResponse::success(
            null,
            'Product deleted successfully.'
        );
    }
}