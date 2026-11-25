<?php

namespace App\Modules\Cart\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => [
                'required',
                'uuid',
                'exists:product_variants,uuid',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'Product variant is required.',
            'product_variant_id.uuid' => 'Product variant ID must be a valid UUID.',
            'product_variant_id.exists' => 'The selected product variant does not exist.',

            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}

