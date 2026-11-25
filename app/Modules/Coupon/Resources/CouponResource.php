<?php

namespace App\Modules\Coupon\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'code' => $this->code,

            'name' => $this->name,

            'description' => $this->description,

            'discount_type' => $this->discount_type,

            'discount_value' => $this->discount_value,

            'maximum_discount_amount' =>
                $this->maximum_discount_amount,

            'minimum_order_amount' =>
                $this->minimum_order_amount,

            'usage_limit' =>
                $this->usage_limit,

            'usage_limit_per_user' =>
                $this->usage_limit_per_user,

            'used_count' =>
                $this->used_count,

            'remaining_usage' =>
                $this->usage_limit !== null
                    ? max(
                        0,
                        $this->usage_limit -
                        $this->used_count
                    )
                    : null,

            'starts_at' =>
                $this->starts_at?->toISOString(),

            'expires_at' =>
                $this->expires_at?->toISOString(),

            'is_active' =>
                $this->is_active,

            'is_first_order_only' =>
                $this->is_first_order_only,

            'is_free_shipping' =>
                $this->is_free_shipping,

            'is_currently_active' =>
                $this->isCurrentlyActive(),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}