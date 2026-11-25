<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->relationLoaded('customerProfile')
            ? $this->customerProfile
            : null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'name' => $this->name,
            'email' => $this->email,

            'is_active' => (bool) $this->is_active,

            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'profile' => $profile ? [
                'uuid' => $profile->uuid,
                'phone' => $profile->phone,
                'avatar' => $profile->avatar,
                'date_of_birth' => $profile->date_of_birth?->format('Y-m-d'),
                'gender' => $profile->gender,
            ] : null,

            'addresses' => $this->whenLoaded(
                'customerAddresses',
                fn () => $this->customerAddresses->map(fn ($address) => [
                    'uuid' => $address->uuid,
                    'label' => $address->label,
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'district' => $address->district,
                    'city' => $address->city,
                    'area' => $address->area,
                    'street_address' => $address->street_address,
                    'landmark' => $address->landmark,
                    'is_default' => $address->is_default,
                    'is_confirmed' => $address->is_confirmed,
                    'created_at' => $address->created_at,
                ])
            ),

            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->pluck('name')->values()
            ),

            'statistics' => [
                'total_orders' => (int) ($this->total_orders ?? 0),
                'paid_orders' => (int) ($this->paid_orders ?? 0),
                'pending_orders' => (int) ($this->pending_orders ?? 0),
                'partially_paid_orders' => (int) ($this->partially_paid_orders ?? 0),
                'cancelled_orders' => (int) ($this->cancelled_orders ?? 0),
                'total_spent' => number_format(
                    (float) ($this->total_spent ?? 0),
                    2,
                    '.',
                    ''
                ),
            ],
        ];
    }
}