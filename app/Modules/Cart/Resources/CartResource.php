<?php

namespace App\Modules\Cart\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'status' => $this->status,

            'currency' => $this->currency,

            'total_quantity' => $this->total_quantity,

            'item_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),

            'subtotal' => $this->subtotal,

            'items' => CartItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

