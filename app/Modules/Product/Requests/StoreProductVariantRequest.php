<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | SKU
            |--------------------------------------------------------------------------
            */

            'sku' => [
                'required',
                'string',
                'max:255',
                'unique:product_variants,sku',
            ],

            /*
            |--------------------------------------------------------------------------
            | Barcode
            |--------------------------------------------------------------------------
            */

            'barcode' => [
                'nullable',
                'string',
                'max:255',
                'unique:product_variants,barcode',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'compare_at_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'cost_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'low_stock_threshold' => [
                'nullable',
                'integer',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Physical Information
            |--------------------------------------------------------------------------
            */

            'weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'image' => [
                'nullable',
                'string',
                'max:500',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Option Values
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | {
            |     "option_value_ids": [6, 9]
            | }
            |
            | 6 = Black
            | 9 = 256 GB
            |
            */

            'option_value_ids' => [
                'nullable',
                'array',
            ],

            'option_value_ids.*' => [
                'integer',
                'distinct',
                'exists:product_option_values,id',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.string' =>
                'Variant name must be a string.',

            'sku.required' =>
                'SKU is required.',

            'sku.unique' =>
                'This SKU is already in use.',

            'barcode.unique' =>
                'This barcode is already in use.',

            'price.numeric' =>
                'Price must be a valid number.',

            'compare_at_price.numeric' =>
                'Compare-at price must be a valid number.',

            'cost_price.numeric' =>
                'Cost price must be a valid number.',

            'stock.integer' =>
                'Stock must be an integer.',

            'low_stock_threshold.integer' =>
                'Low stock threshold must be an integer.',

            'weight.numeric' =>
                'Weight must be a valid number.',

            'is_active.boolean' =>
                'Active status must be true or false.',

            'sort_order.integer' =>
                'Sort order must be an integer.',

            'option_value_ids.array' =>
                'Option value IDs must be an array.',

            'option_value_ids.*.integer' =>
                'Each option value ID must be an integer.',

            'option_value_ids.*.distinct' =>
                'Duplicate option value IDs are not allowed.',

            'option_value_ids.*.exists' =>
                'One or more option values do not exist.',
        ];
    }
}

