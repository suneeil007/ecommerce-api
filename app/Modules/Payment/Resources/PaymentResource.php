<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'uuid' => $this->uuid,

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            'order' => $this->when(
                $this->relationLoaded('order') &&
                $this->order,
                [
                    'uuid' => $this->order->uuid,

                    'order_number' =>
                        $this->order->order_number,

                    'status' =>
                        $this->order->status?->value,

                    'payment_status' =>
                        $this->order->payment_status?->value,

                    'total_amount' =>
                        (float) $this->order->total_amount,

                    'currency' =>
                        $this->order->currency,
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            |
            | Available when order.user is eager loaded.
            | Admin payment queries already load order.user.
            |
            */

            'customer' => $this->when(
                $this->relationLoaded('order') &&
                $this->order &&
                $this->order->relationLoaded('user') &&
                $this->order->user,
                [
                    'id' =>
                        $this->order->user->id,

                    'name' =>
                        $this->order->user->name,

                    'email' =>
                        $this->order->user->email,
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Payment Information
            |--------------------------------------------------------------------------
            */

            'method' =>
                $this->method?->value,

            'status' =>
                $this->status?->value,

            'currency' =>
                $this->currency,

            'amount' =>
                (float) $this->amount,

            'reference' =>
                $this->reference,

            'notes' =>
                $this->notes,

            'paid_at' =>
                $this->paid_at?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Transactions
            |--------------------------------------------------------------------------
            */

            'transactions' =>
                PaymentTransactionResource::collection(
                    $this->whenLoaded('transactions')
                ),

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}
