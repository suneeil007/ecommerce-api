<?php

namespace App\Modules\Profile\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'label' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'recipient_name' => [
                'sometimes',
                'required',
                'string',
                'max:191',
            ],

            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:30',
            ],

            'district' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'city' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'area' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],

            'street_address' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'landmark' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'is_default' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'recipient_name.required' =>
                'Recipient name is required.',

            'phone.required' =>
                'Phone number is required.',

            'district.required' =>
                'District is required.',

            'city.required' =>
                'City is required.',

            'street_address.required' =>
                'Street address is required.',

            'is_default.boolean' =>
                'Default address must be either true or false.',
        ];
    }
}
