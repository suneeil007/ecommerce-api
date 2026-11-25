<?php

namespace App\Modules\Product\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Requests\StoreProductVariantRequest;
use App\Modules\Product\Requests\UpdateProductVariantOptionsRequest;
use App\Modules\Product\Requests\UpdateProductVariantRequest;
use App\Modules\Product\Resources\ProductVariantResource;
use App\Modules\Product\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    public function __construct(
        protected ProductVariantService $variantService
    ) {
    }

    /**
     * GET /api/admin/products/{uuid}/variants
     */
    public function index(string $uuid): JsonResponse
    {
        $product = $this->findProduct($uuid);

        $variants = $this->variantService
            ->getByProduct($product);

        return ApiResponse::success(
            ProductVariantResource::collection($variants),
            'Product variants retrieved successfully.'
        );
    }

    /**
     * POST /api/admin/products/{uuid}/variants
     */
    public function store(
        StoreProductVariantRequest $request,
        string $uuid
    ): JsonResponse {
        $product = $this->findProduct($uuid);

        $variant = $this->variantService->create(
            $product,
            $request->validated()
        );

        return ApiResponse::success(
            new ProductVariantResource($variant),
            'Product variant created successfully.',
            201
        );
    }

    /**
     * GET /api/admin/products/{uuid}/variants/{variant}
     */
    public function show(
        string $uuid,
        string $variant
    ): JsonResponse {
        $product = $this->findProduct($uuid);

        $productVariant = $this->variantService
            ->findByUuid(
                $product,
                $variant
            );

        if (!$productVariant) {
            return ApiResponse::error(
                'Product variant not found.',
                404
            );
        }

        return ApiResponse::success(
            new ProductVariantResource($productVariant),
            'Product variant retrieved successfully.'
        );
    }

    /**
     * PUT/PATCH /api/admin/products/{uuid}/variants/{variant}
     */
    public function update(
        UpdateProductVariantRequest $request,
        string $uuid,
        string $variant
    ): JsonResponse {
        $product = $this->findProduct($uuid);

        $productVariant = $this->variantService
            ->findByUuid(
                $product,
                $variant
            );

        if (!$productVariant) {
            return ApiResponse::error(
                'Product variant not found.',
                404
            );
        }

        $productVariant = $this->variantService->update(
            $productVariant,
            $request->validated()
        );

        return ApiResponse::success(
            new ProductVariantResource($productVariant),
            'Product variant updated successfully.'
        );
    }

    /**
     * DELETE /api/admin/products/{uuid}/variants/{variant}
     */
    public function destroy(
        string $uuid,
        string $variant
    ): JsonResponse {
        $product = $this->findProduct($uuid);

        $productVariant = $this->variantService
            ->findByUuid(
                $product,
                $variant
            );

        if (!$productVariant) {
            return ApiResponse::error(
                'Product variant not found.',
                404
            );
        }

        $this->variantService->delete(
            $productVariant
        );

        return ApiResponse::success(
            null,
            'Product variant deleted successfully.'
        );
    }

    /**
     * PUT /api/admin/products/{uuid}/variants/{variant}/options
     */
    public function updateOptions(
        UpdateProductVariantOptionsRequest $request,
        string $uuid,
        string $variant
    ): JsonResponse {
        $product = $this->findProduct($uuid);

        $productVariant = $this->variantService
            ->findByUuid(
                $product,
                $variant
            );

        if (!$productVariant) {
            return ApiResponse::error(
                'Product variant not found.',
                404
            );
        }

        $optionValueIds = $request->validated(
            'option_value_ids'
        );

        $productVariant = $this->variantService
            ->updateOptions(
                $productVariant,
                $optionValueIds
            );

        return ApiResponse::success(
            new ProductVariantResource($productVariant),
            'Product variant options updated successfully.'
        );
    }

    /**
     * Find product by UUID.
     */
    protected function findProduct(string $uuid): Product
    {
        return Product::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}

