<?php

namespace App\Modules\Cart\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $variant = $this->whenLoaded('productVariant');

        return [
            'uuid' => $this->uuid,

            'quantity' => (int) $this->quantity,

            'unit_price' => (float) $this->unit_price,

            'total_price' => (float) $this->total_price,

            'product' => $product ? [
                'id' => $product->uuid,
                'name' => $product->name,
                'display_name' => $product->display_name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price !== null
                    ? (float) $product->price
                    : null,
                'image' => $this->getProductImage($product),
            ] : null,

            'variant' => $variant ? [
                'uuid' => $variant->uuid,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,

                'price' => $variant->price !== null
                    ? (float) $variant->price
                    : null,

                'stock' => (int) $variant->stock,

                'is_active' => (bool) $variant->is_active,

                'image' => $variant->image
                    ? asset(ltrim($variant->image, '/'))
                    : null,

                'options' => $this->getVariantOptions($variant),
            ] : null,
        ];
    }

    /**
     * Get the product image URL.
     */
    protected function getProductImage($product): ?string
    {
        if (
            !$product->relationLoaded('primaryImage') &&
            !$product->relationLoaded('images')
        ) {
            return null;
        }

        $image = null;

        if ($product->relationLoaded('primaryImage')) {
            $image = $product->primaryImage;
        }

        if (!$image && $product->relationLoaded('images')) {
            $image = $product->images->first();
        }

        if (!$image || !$image->image) {
            return null;
        }

        return asset(
            ltrim($image->image, '/')
        );
    }

    /**
     * Get variant option values.
     */
    protected function getVariantOptions($variant): array
    {
        if (!$variant->relationLoaded('optionValues')) {
            return [];
        }

        return $variant->optionValues
            ->map(function ($optionValue) {
                $option = $optionValue->relationLoaded('productOption')
                    ? $optionValue->productOption
                    : null;

                return [
                    'option' => $option ? [
                        'uuid' => $option->uuid,
                        'name' => $option->name,
                        'display_name' => $option->display_name,
                    ] : null,

                    'value' => [
                        'uuid' => $optionValue->uuid,
                        'value' => $optionValue->value,
                        'display_value' => $optionValue->display_value,
                        'color_code' => $optionValue->color_code,
                    ],
                ];
            })
            ->values()
            ->toArray();
    }
}

