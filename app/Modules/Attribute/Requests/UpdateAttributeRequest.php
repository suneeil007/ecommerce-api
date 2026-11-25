<?php

namespace App\Modules\Attribute\Requests;

use App\Modules\Attribute\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');

        $attributeId = Attribute::where(
            'uuid',
            $uuid
        )->value('id');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($attributeId) {
                    $slug = Str::slug($value);

                    $exists = Attribute::query()
                        ->where('slug', $slug)
                        ->where('id', '!=', $attributeId)
                        ->exists();

                    if ($exists) {
                        $fail(
                            'An attribute with this name already exists.'
                        );
                    }
                },
            ],

            'display_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }
}

