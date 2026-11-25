<?php

namespace App\Modules\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:191',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'processing',
                    'paid',
                    'partially_paid',
                    'failed',
                    'cancelled',
                    'refunded',
                ]),
            ],

            'method' => [
                'nullable',
                Rule::in([
                    'cod',
                    'manual',
                    'esewa',
                    'khalti',
                    'bank_transfer',
                ]),
            ],

            'from' => [
                'nullable',
                'date',
                'required_with:to',
                'before_or_equal:to',
            ],

            'to' => [
                'nullable',
                'date',
                'required_with:from',
                'after_or_equal:from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'created_at',
                    'amount',
                    'paid_at',
                    'status',
                ]),
            ],

            'direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],
        ];
    }
}
