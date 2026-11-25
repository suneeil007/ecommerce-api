<?php

namespace App\Modules\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Variant Information
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,
            'uuid' => $this->uuid,

            'product_id' => $this->product_id,

            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'cost_price' => $this->cost_price,

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            'stock' => $this->stock,
            'low_stock_threshold' => $this->low_stock_threshold,

            'weight' => $this->weight,

            /*
            |--------------------------------------------------------------------------
            | Image
            |--------------------------------------------------------------------------
            */

            'image' => $this->image,

            'image_url' => $this->image
                ? asset(ltrim($this->image, '/'))
                : null,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'product' => $this->whenLoaded(
                'product',
                fn () => [
                    'id' => $this->product->id,
                    'uuid' => $this->product->uuid,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                    'display_name' => $this->product->display_name,
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Option Values
            |--------------------------------------------------------------------------
            */

            'option_values' => $this->whenLoaded(
                'optionValues',
                fn () => $this->optionValues
                    ->map(
                        fn ($optionValue) => [
                            /*
                            |----------------------------------------------------------------------
                            | Option Value
                            |----------------------------------------------------------------------
                            */

                            'id' => $optionValue->id,
                            'uuid' => $optionValue->uuid,

                            /*
                            |----------------------------------------------------------------------
                            | Parent Option
                            |----------------------------------------------------------------------
                            */

                            'option_id' =>
                                $optionValue->productOption?->id,

                            'option_uuid' =>
                                $optionValue->productOption?->uuid,

                            'option_name' =>
                                $optionValue->productOption?->name,

                            'option_slug' =>
                                $optionValue->productOption?->slug,

                            'option_display_name' =>
                                $optionValue->productOption?->display_name,

                            /*
                            |----------------------------------------------------------------------
                            | Value
                            |----------------------------------------------------------------------
                            */

                            'value' => $optionValue->value,

                            'slug' => $optionValue->slug,

                            'display_value' =>
                                $optionValue->display_value,

                            'color_code' =>
                                $optionValue->color_code,
                        ]
                    )
                    ->values()
            ),

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

