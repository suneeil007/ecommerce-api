<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the order item into an array.
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $variant = $this->productVariant;

        /*
        |--------------------------------------------------------------------------
        | Product Image
        |--------------------------------------------------------------------------
        |
        | Priority:
        | 1. Selected variant image
        | 2. Product primary image
        | 3. First product gallery image
        |
        | ProductImage stores the image path in its "image" column.
        |
        */

        $image = $variant?->image;

        if (blank($image) && $product) {
            // Prefer the product's primary image.
            $primaryImage = $product->primaryImage;

            // Fall back to the first image in the gallery.
            $firstImage = $product->images->first();

            $image = $primaryImage?->image
                ?: $firstImage?->image;
        }

        /*
        |--------------------------------------------------------------------------
        | Order Item Response
        |--------------------------------------------------------------------------
        */

        return [
            'uuid' => $this->uuid,

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'product' => [
                'uuid' => $product?->uuid,

                'name' => $this->product_name,

                'display_name' => $product?->display_name,

                'slug' => $product?->slug,

                'product_type' => $product?->product_type instanceof \BackedEnum
                    ? $product->product_type->value
                    : $product?->product_type,

                'image' => $image,
            ],

            /*
            |--------------------------------------------------------------------------
            | Selected Variant
            |--------------------------------------------------------------------------
            */

            'variant' => $variant
                ? [
                    'uuid' => $variant->uuid,

                    'name' => $this->variant_name,

                    'sku' => $this->sku,

                    'barcode' => $variant->barcode,

                    'image' => $variant->image,
                ]
                : null,

            /*
            |--------------------------------------------------------------------------
            | Quantity and Pricing
            |--------------------------------------------------------------------------
            */

            'quantity' => (int) $this->quantity,

            'unit_price' => $this->unit_price,

            'discount_amount' => $this->discount_amount,

            'tax_amount' => $this->tax_amount,

            'total_price' => $this->total_price,
        ];
    }
}