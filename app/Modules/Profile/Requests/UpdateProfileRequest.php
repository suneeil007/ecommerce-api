<?php

namespace App\Modules\Profile\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],

            'gender' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'phone.max' => 'Phone number may not exceed 30 characters.',

            'date_of_birth.date' => 'Please provide a valid date of birth.',

            'date_of_birth.before' =>
                'Date of birth must be before today.',

            'gender.max' =>
                'Gender may not exceed 30 characters.',
        ];
    }
}
