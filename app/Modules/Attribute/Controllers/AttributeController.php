<?php

namespace App\Modules\Attribute\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Attribute\Requests\StoreAttributeRequest;
use App\Modules\Attribute\Requests\StoreAttributeValueRequest;
use App\Modules\Attribute\Requests\UpdateAttributeRequest;
use App\Modules\Attribute\Requests\UpdateAttributeValueRequest;
use App\Modules\Attribute\Resources\AttributeResource;
use App\Modules\Attribute\Resources\AttributeValueResource;
use App\Modules\Attribute\Services\AttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function __construct(
        protected AttributeService $attributeService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $attributes = $this->attributeService->getAll(
            $perPage
        );

        return ApiResponse::success(
            [
                'items' => AttributeResource::collection(
                    $attributes->items()
                ),

                'pagination' => [
                    'current_page' => $attributes->currentPage(),
                    'last_page' => $attributes->lastPage(),
                    'per_page' => $attributes->perPage(),
                    'total' => $attributes->total(),
                    'from' => $attributes->firstItem(),
                    'to' => $attributes->lastItem(),
                ],
            ],
            'Attributes retrieved successfully.'
        );
    }

    public function store(
        StoreAttributeRequest $request
    ): JsonResponse {
        $attribute = $this->attributeService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new AttributeResource($attribute),
            'Attribute created successfully.',
            201
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $attribute = $this->attributeService->findByUuid(
            $uuid
        );

        if (!$attribute) {
            return ApiResponse::error(
                'Attribute not found.',
                404
            );
        }

        return ApiResponse::success(
            new AttributeResource($attribute),
            'Attribute retrieved successfully.'
        );
    }

    public function update(
        UpdateAttributeRequest $request,
        string $uuid
    ): JsonResponse {
        $attribute = $this->attributeService->findByUuid(
            $uuid
        );

        if (!$attribute) {
            return ApiResponse::error(
                'Attribute not found.',
                404
            );
        }

        $attribute = $this->attributeService->update(
            $attribute,
            $request->validated()
        );

        return ApiResponse::success(
            new AttributeResource($attribute),
            'Attribute updated successfully.'
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $attribute = $this->attributeService->findByUuid(
            $uuid
        );

        if (!$attribute) {
            return ApiResponse::error(
                'Attribute not found.',
                404
            );
        }

        $this->attributeService->delete($attribute);

        return ApiResponse::success(
            null,
            'Attribute deleted successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Attribute Values
    |--------------------------------------------------------------------------
    */

    public function values(
        Request $request,
        string $uuid
    ): JsonResponse {
        $attribute = $this->attributeService->findByUuid(
            $uuid
        );

        if (!$attribute) {
            return ApiResponse::error(
                'Attribute not found.',
                404
            );
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $values = $this->attributeService->getValues(
            $attribute,
            $perPage
        );

        return ApiResponse::success(
            [
                'items' => AttributeValueResource::collection(
                    $values->items()
                ),

                'pagination' => [
                    'current_page' => $values->currentPage(),
                    'last_page' => $values->lastPage(),
                    'per_page' => $values->perPage(),
                    'total' => $values->total(),
                    'from' => $values->firstItem(),
                    'to' => $values->lastItem(),
                ],
            ],
            'Attribute values retrieved successfully.'
        );
    }

    public function storeValue(
        StoreAttributeValueRequest $request,
        string $uuid
    ): JsonResponse {
        $attribute = $this->attributeService->findByUuid(
            $uuid
        );

        if (!$attribute) {
            return ApiResponse::error(
                'Attribute not found.',
                404
            );
        }

        $value = $this->attributeService->createValue(
            $attribute,
            $request->validated()
        );

        return ApiResponse::success(
            new AttributeValueResource($value),
            'Attribute value created successfully.',
            201
        );
    }

    public function updateValue(
        UpdateAttributeValueRequest $request,
        string $uuid,
        string $valueUuid
    ): JsonResponse {
        $attribute = $this->attributeService->findByUuid(
            $uuid
        );

        if (!$attribute) {
            return ApiResponse::error(
                'Attribute not found.',
                404
            );
        }

        $value = $this->attributeService->findValueByUuid(
            $valueUuid
        );

        if (!$value || $value->attribute_id !== $attribute->id) {
            return ApiResponse::error(
                'Attribute value not found.',
                404
            );
        }

        $value = $this->attributeService->updateValue(
            $value,
            $request->validated()
        );

        return ApiResponse::success(
            new AttributeValueResource($value),
            'Attribute value updated successfully.'
        );
    }

    public function destroyValue(
        string $uuid,
        string $valueUuid
    ): JsonResponse {
        $attribute = $this->attributeService->findByUuid(
            $uuid
        );

        if (!$attribute) {
            return ApiResponse::error(
                'Attribute not found.',
                404
            );
        }

        $value = $this->attributeService->findValueByUuid(
            $valueUuid
        );

        if (!$value || $value->attribute_id !== $attribute->id) {
            return ApiResponse::error(
                'Attribute value not found.',
                404
            );
        }

        $this->attributeService->deleteValue($value);

        return ApiResponse::success(
            null,
            'Attribute value deleted successfully.'
        );
    }
}

