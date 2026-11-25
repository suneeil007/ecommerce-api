<?php

namespace App\Modules\Attribute\Requests;

use App\Modules\Attribute\Models\AttributeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valueUuid = $this->route('valueUuid');

        $valueId = AttributeValue::where(
            'uuid',
            $valueUuid
        )->value('id');

        return [
            'value' => [
                'sometimes',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($valueId) {
                    $attributeId = AttributeValue::where(
                        'id',
                        $valueId
                    )->value('attribute_id');

                    $slug = Str::slug($value);

                    $exists = AttributeValue::query()
                        ->where('attribute_id', $attributeId)
                        ->where('slug', $slug)
                        ->where('id', '!=', $valueId)
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

