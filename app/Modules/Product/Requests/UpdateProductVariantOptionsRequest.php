<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariantOptionsRequest extends FormRequest
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
                'required',
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
            'option_value_ids.required' =>
                'Option value IDs are required.',

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

