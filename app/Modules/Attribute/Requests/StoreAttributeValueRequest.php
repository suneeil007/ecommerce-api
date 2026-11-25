<?php

namespace App\Modules\Attribute\Requests;

use App\Modules\Attribute\Models\AttributeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attributeUuid = $this->route('uuid');

        $attributeId = \App\Modules\Attribute\Models\Attribute::where(
            'uuid',
            $attributeUuid
        )->value('id');

        return [
            'value' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($attributeId) {
                    $slug = Str::slug($value);

                    $exists = AttributeValue::query()
                        ->where('attribute_id', $attributeId)
                        ->where('slug', $slug)
                        ->exists();

                    if ($exists) {
                        $fail(
                            'This value already exists for this attribute.'
                        );
                    }
                },
            ],

            'display_value' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }
}

