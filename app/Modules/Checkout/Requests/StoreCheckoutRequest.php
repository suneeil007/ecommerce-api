<?php

namespace App\Modules\Checkout\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function validatedData(): array
    {
        return [
            'notes' => $this->validated('notes'),
        ];
    }
}

