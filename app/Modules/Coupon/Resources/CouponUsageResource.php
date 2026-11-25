<?php

namespace App\Modules\Coupon\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'coupon_uuid' =>
                $this->coupon?->uuid,

            'coupon_code' =>
                $this->coupon?->code,

            'user_id' =>
                $this->user_id,

            'order_id' =>
                $this->order_id,

            'discount_amount' =>
                $this->discount_amount,

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}