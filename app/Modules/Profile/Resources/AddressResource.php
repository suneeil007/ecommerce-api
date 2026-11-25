<?php

namespace App\Modules\Profile\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'label' => $this->label,

            'recipient_name' => $this->recipient_name,

            'phone' => $this->phone,

            'district' => $this->district,

            'city' => $this->city,

            'area' => $this->area,

            'street_address' => $this->street_address,

            'landmark' => $this->landmark,

            'is_default' => (bool) $this->is_default,

            'is_confirmed' => (bool) $this->is_confirmed,

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
