<?php

namespace App\Modules\Payment\Requests;

use App\Modules\Payment\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'order_uuid' => [
                'required',
                'uuid',
            ],

            'method' => [
                'required',
                'string',
                Rule::in(
                    array_column(
                        PaymentMethod::cases(),
                        'value'
                    )
                ),
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:191',
            ],

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
            'order_uuid' => $this->validated('order_uuid'),
            'method' => $this->validated('method'),
            'amount' => (float) $this->validated('amount'),
            'reference' => $this->validated('reference'),
            'notes' => $this->validated('notes'),
        ];
    }
}
