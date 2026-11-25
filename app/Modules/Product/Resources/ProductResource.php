<?php

namespace App\Modules\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            
            'id' => $this->id,
            'uuid' => $this->uuid,

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'name' => $this->name,
            'slug' => $this->slug,
            'display_name' => $this->display_name,

            /*
            |--------------------------------------------------------------------------
            | Product Type
            |--------------------------------------------------------------------------
            |
            | Examples:
            |
            | simple
            | variable
            |
            | This is required by the React ProductForm so that the edit form
            | knows whether the product is currently simple or variable.
            |
            */

            'product_type' => $this->product_type,

            'short_description' => $this->short_description,
            'description' => $this->description,

            /*
            |--------------------------------------------------------------------------
            | SKU / Pricing
            |--------------------------------------------------------------------------
            */

            'sku' => $this->sku,

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
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category' => $this->whenLoaded(
                'category',
                fn () => [
                    'id' => $this->category->id,
                    'uuid' => $this->category->uuid,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Images
            |--------------------------------------------------------------------------
            */

            'images' => $this->whenLoaded(
                'images',
                fn () => $this->images->map(
                    fn ($image) => [
                        'id' => $image->id,
                        'uuid' => $image->uuid,
                        'image' => $image->image,
                        'url' => $this->imageUrl($image->image),
                        'alt_text' => $image->alt_text,
                        'sort_order' => $image->sort_order,
                        'is_primary' => (bool) $image->is_primary,
                    ]
                )->values()
            ),

            /*
            |--------------------------------------------------------------------------
            | Primary Image
            |--------------------------------------------------------------------------
            */

            'primary_image' => $this->whenLoaded(
                'primaryImage',
                fn () => $this->primaryImage
                    ? [
                        'id' => $this->primaryImage->id,
                        'uuid' => $this->primaryImage->uuid,
                        'image' => $this->primaryImage->image,
                        'url' => $this->imageUrl(
                            $this->primaryImage->image
                        ),
                        'alt_text' => $this->primaryImage->alt_text,
                    ]
                    : null
            ),

            /*
            |--------------------------------------------------------------------------
            | Product Attribute Values
            |--------------------------------------------------------------------------
            |
            | These are the general attributes assigned directly to the product.
            |
            */

            'attribute_values' => $this->whenLoaded(
                'attributeValues',
                fn () => $this->attributeValues->map(
                    fn ($value) => [
                        'id' => $value->id,
                        'uuid' => $value->uuid,
                        'value' => $value->value,
                        'slug' => $value->slug,
                        'display_value' => $value->display_value,

                        'attribute' => $value->relationLoaded('attribute')
                            ? [
                                'id' => $value->attribute->id,
                                'uuid' => $value->attribute->uuid,
                                'name' => $value->attribute->name,
                                'slug' => $value->attribute->slug,
                                'display_name' => $value->attribute->display_name,
                            ]
                            : null,
                    ]
                )->values()
            ),

            /*
            |--------------------------------------------------------------------------
            | Product Options
            |--------------------------------------------------------------------------
            |
            | ProductOption is the product-specific use of a master Attribute.
            |
            */

            'options' => $this->whenLoaded(
                'options',
                fn () => $this->options->map(
                    fn ($option) => [
                        'id' => $option->id,
                        'uuid' => $option->uuid,

                        /*
                        |--------------------------------------------------------------------------
                        | Master Attribute
                        |--------------------------------------------------------------------------
                        */

                        'attribute' => $option->relationLoaded('attribute')
                            ? [
                                'id' => $option->attribute->id,
                                'uuid' => $option->attribute->uuid,
                                'name' => $option->attribute->name,
                                'slug' => $option->attribute->slug,
                                'display_name' => $option->attribute->display_name,
                                'description' => $option->attribute->description,
                                'is_active' => (bool) $option->attribute->is_active,
                                'sort_order' => $option->attribute->sort_order,
                            ]
                            : null,

                        /*
                        |--------------------------------------------------------------------------
                        | Legacy / Product Option Fields
                        |--------------------------------------------------------------------------
                        */

                        'name' => $option->name,
                        'slug' => $option->slug,
                        'display_name' => $option->display_name,
                        'sort_order' => $option->sort_order,

                        /*
                        |--------------------------------------------------------------------------
                        | Product Option Values
                        |--------------------------------------------------------------------------
                        */

                        'values' => $option->relationLoaded('values')
                            ? $option->values->map(
                                fn ($value) => [
                                    'id' => $value->id,
                                    'uuid' => $value->uuid,

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Master Attribute Value
                                    |--------------------------------------------------------------------------
                                    */

                                    'attribute_value' => $value->relationLoaded('attributeValue')
                                        ? [
                                            'id' => $value->attributeValue->id,
                                            'uuid' => $value->attributeValue->uuid,
                                            'attribute_id' => $value->attributeValue->attribute_id,
                                            'value' => $value->attributeValue->value,
                                            'slug' => $value->attributeValue->slug,
                                            'display_value' => $value->attributeValue->display_value,
                                        ]
                                        : null,

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Legacy / Product Option Value Fields
                                    |--------------------------------------------------------------------------
                                    */

                                    'value' => $value->value,
                                    'slug' => $value->slug,
                                    'display_value' => $value->display_value,
                                    'color_code' => $value->color_code,
                                    'sort_order' => $value->sort_order,
                                ]
                            )->values()
                            : [],
                    ]
                )->values()
            ),

            /*
            |--------------------------------------------------------------------------
            | Product Variants
            |--------------------------------------------------------------------------
            */

            'variants' => $this->whenLoaded(
                'variants',
                fn () => $this->variants->map(
                    fn ($variant) => [
                        'id' => $variant->id,
                        'uuid' => $variant->uuid,

                        /*
                        |--------------------------------------------------------------------------
                        | Variant Information
                        |--------------------------------------------------------------------------
                        */

                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'barcode' => $variant->barcode,

                        /*
                        |--------------------------------------------------------------------------
                        | Variant Pricing
                        |--------------------------------------------------------------------------
                        */

                        'price' => $variant->price,
                        'compare_at_price' => $variant->compare_at_price,
                        'cost_price' => $variant->cost_price,

                        /*
                        |--------------------------------------------------------------------------
                        | Variant Inventory
                        |--------------------------------------------------------------------------
                        */

                        'stock' => $variant->stock,
                        'low_stock_threshold' => $variant->low_stock_threshold,
                        'weight' => $variant->weight,

                        /*
                        |--------------------------------------------------------------------------
                        | Variant Image
                        |--------------------------------------------------------------------------
                        */

                        'image' => $variant->image,

                        'image_url' => $variant->image
                            ? $this->imageUrl($variant->image)
                            : null,

                        /*
                        |--------------------------------------------------------------------------
                        | Variant Status
                        |--------------------------------------------------------------------------
                        */

                        'is_active' => (bool) $variant->is_active,
                        'sort_order' => $variant->sort_order,

                        /*
                        |--------------------------------------------------------------------------
                        | Variant Option Values
                        |--------------------------------------------------------------------------
                        */

                        'option_values' => $variant->relationLoaded('optionValues')
                            ? $variant->optionValues->map(
                                fn ($optionValue) => [
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Product Option Value
                                    |--------------------------------------------------------------------------
                                    */

                                    'id' => $optionValue->id,
                                    'uuid' => $optionValue->uuid,

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Parent Product Option
                                    |--------------------------------------------------------------------------
                                    */

                                    'option_id' => $optionValue->productOption?->id,
                                    'option_uuid' => $optionValue->productOption?->uuid,
                                    'option_name' => $optionValue->productOption?->name,
                                    'option_slug' => $optionValue->productOption?->slug,
                                    'option_display_name' => $optionValue->productOption?->display_name,

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Master Attribute
                                    |--------------------------------------------------------------------------
                                    */

                                    'attribute' => $optionValue->productOption?->relationLoaded('attribute')
                                        ? [
                                            'id' => $optionValue->productOption->attribute->id,
                                            'uuid' => $optionValue->productOption->attribute->uuid,
                                            'name' => $optionValue->productOption->attribute->name,
                                            'slug' => $optionValue->productOption->attribute->slug,
                                            'display_name' => $optionValue->productOption->attribute->display_name,
                                        ]
                                        : null,

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Master Attribute Value
                                    |--------------------------------------------------------------------------
                                    */

                                    'attribute_value' => $optionValue->relationLoaded('attributeValue')
                                        ? [
                                            'id' => $optionValue->attributeValue->id,
                                            'uuid' => $optionValue->attributeValue->uuid,
                                            'attribute_id' => $optionValue->attributeValue->attribute_id,
                                            'value' => $optionValue->attributeValue->value,
                                            'slug' => $optionValue->attributeValue->slug,
                                            'display_value' => $optionValue->attributeValue->display_value,
                                        ]
                                        : null,

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Legacy / Product Option Value Fields
                                    |--------------------------------------------------------------------------
                                    */

                                    'value' => $optionValue->value,
                                    'slug' => $optionValue->slug,
                                    'display_value' => $optionValue->display_value,
                                    'color_code' => $optionValue->color_code,
                                ]
                            )->values()
                            : [],
                    ]
                )->values()
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

    /**
     * Generate public image URL.
     *
     * Images are stored inside:
     *
     * public/products/
     *
     * Database value:
     *
     * products/example.jpg
     *
     * Result:
     *
     * http://localhost/ecommerce-api/public/products/example.jpg
     */
    protected function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset(ltrim($path, '/'));
    }
}

