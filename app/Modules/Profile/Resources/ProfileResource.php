<?php

namespace App\Modules\Profile\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'user' => [
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],

            'phone' => $this->phone,

            'avatar' => $this->avatar,

            'date_of_birth' => $this->date_of_birth
                ? $this->date_of_birth->format('Y-m-d')
                : null,

            'gender' => $this->gender,

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
