<?php

namespace App\Modules\Coupon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Route parameter
        |--------------------------------------------------------------------------
        |
        | Route:
        | /admin/coupons/{uuid}
        |
        | Therefore we must retrieve "uuid", not "coupon".
        |
        */

        $couponUuid = $this->route('uuid');

        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',

                Rule::unique(
                    'coupons',
                    'code'
                )->ignore(
                    $couponUuid,
                    'uuid'
                ),
            ],

            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'discount_type' => [
                'sometimes',
                Rule::in([
                    'fixed',
                    'percentage',
                ]),
            ],

            'discount_value' => [
                'sometimes',
                'numeric',
                'min:0.01',
            ],

            'maximum_discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'minimum_order_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'usage_limit' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'usage_limit_per_user' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'starts_at' => [
                'nullable',
                'date',
            ],

            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'is_first_order_only' => [
                'sometimes',
                'boolean',
            ],

            'is_free_shipping' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (
                $this->discount_type === 'percentage' &&
                $this->discount_value > 100
            ) {
                $validator->errors()->add(
                    'discount_value',
                    'Percentage discount cannot exceed 100%.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(
                    trim($this->code)
                ),
            ]);
        }
    }
}

