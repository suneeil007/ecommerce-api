<?php

namespace App\Modules\Checkout\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'status' => $this->status?->value,

            'currency' => $this->currency,

            'pricing' => [
                'subtotal' => (float) $this->subtotal,
                'discount_amount' => (float) $this->discount_amount,
                'shipping_amount' => (float) $this->shipping_amount,
                'tax_amount' => (float) $this->tax_amount,
                'total_amount' => (float) $this->total_amount,
            ],

            'coupon_code' => $this->coupon_code,

            'notes' => $this->notes,

            'customer' => $this->when(
                $this->relationLoaded('user') && $this->user,
                [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ]
            ),

            'cart' => $this->when(
                $this->relationLoaded('cart') && $this->cart,
                [
                    'uuid' => $this->cart->uuid,
                    'status' => $this->cart->status,
                    'currency' => $this->cart->currency,
                ]
            ),

            'items' => CheckoutItemResource::collection(
                $this->whenLoaded('items')
            ),

            'item_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),

            'total_quantity' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->sum('quantity')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

