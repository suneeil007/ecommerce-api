<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;

        $customerName = $this->customer_name ?: $user?->name;
        $customerEmail = $this->email ?: $user?->email;
        $customerPhone = $this->phone;

        $district = $this->district;
        $city = $this->city;
        $address = $this->address;

        return [
            // Identity
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,

            // Status
            'status' => $this->enumValue($this->status),
            'payment_status' => $this->enumValue($this->payment_status),
            'fulfillment_status' => $this->enumValue(
                $this->fulfillment_status
            ),

            // Currency
            'currency' => $this->currency,

            // Payment
            'payment_method' => $this->payment_method,

            // Amounts: canonical API fields
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,

            // Compatibility aliases for existing frontend components
            'shipping_fee' => $this->shipping_amount,
            'total' => $this->total_amount,

            // Customer and recipient
            'customer' => [
                'id' => $this->user?->id,
                'name' => $this->customer_name ?: $this->user?->name,
                'phone' => $this->phone,
                'email' => $this->email ?: $this->user?->email,
                'district' => $this->district,
                'city' => $this->city,
                'address' => $this->address,

            ],

            // Explicit delivery address
            'shipping_address' => [
                'name' => $this->customer_name ?: $this->user?->name,
                'phone' => $this->phone,
                'email' => $this->email ?: $this->user?->email,
                'district' => $this->district,
                'city' => $this->city,
                'address' => $this->address,
            ],

            // Items
            'item_count' => $this->item_count,
            'total_quantity' => $this->total_quantity,
            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            // Notes
            'notes' => $this->notes,

            // Dates
            'placed_at' => $this->placed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum
            ? $value->value
            : $value;
    }
}