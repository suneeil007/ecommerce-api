<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Customer Information
            |--------------------------------------------------------------------------
            */

            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Delivery Information
            |--------------------------------------------------------------------------
            */

            'province_id' => [
                'required',
                'integer',
                'exists:provinces,id',
            ],

            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')
                    ->where(
                        'province_id',
                        $this->input('province_id')
                    ),
            ],

            'city' => [
                'required',
                'string',
                'max:100',
            ],

            'address' => [
                'required',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Additional Information
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_method' => [
                'required',
                'in:cod',
            ],

            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */

            'coupon_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_uuid' => [
                'required',
                'string',
                'uuid',
            ],

            'items.*.variant_uuid' => [
                'nullable',
                'string',
                'uuid',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
