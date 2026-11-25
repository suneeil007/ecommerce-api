<?php

namespace App\Modules\Checkout\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = null;

        if (
            $this->relationLoaded('product') &&
            $this->product &&
            $this->product->relationLoaded('primaryImage') &&
            $this->product->primaryImage
        ) {
            $imagePath = $this->product->primaryImage->image_path
                ?? $this->product->primaryImage->path
                ?? null;

            if ($imagePath) {
                $image = asset(ltrim($imagePath, '/'));
            }
        }

        return [
            'uuid' => $this->uuid,

            'product' => [
                'id' => $this->product_id,
                'uuid' => $this->product?->uuid,
                'name' => $this->product_name,
                'slug' => $this->product?->slug,
                'image' => $image,
            ],

            'variant' => $this->when(
                $this->product_variant_id,
                [
                    'id' => $this->product_variant_id,
                    'uuid' => $this->productVariant?->uuid,
                    'name' => $this->variant_name,
                    'sku' => $this->sku,
                ]
            ),

            'quantity' => $this->quantity,

            'unit_price' => (float) $this->unit_price,

            'discount_amount' => (float) $this->discount_amount,

            'tax_amount' => (float) $this->tax_amount,

            'total_price' => (float) $this->total_price,
        ];
    }
}

