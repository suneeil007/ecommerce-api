<?php

namespace App\Modules\Category\Requests;

use App\Modules\Category\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');

        $category = Category::where('uuid', $uuid)->first();

        return [

            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {

                    if (
                        $category &&
                        (int) $value === (int) $category->id
                    ) {
                        $fail(
                            'A category cannot be its own parent.'
                        );
                    }
                },
            ],

            'name' => [
                'sometimes',
                'string',
                'max:191',
                Rule::unique('categories', 'name')
                    ->ignore($category?->id),
            ],

            'display_name' => [
                'nullable',
                'string',
                'max:191',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
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

    public function messages(): array
    {
        return [
            'name.unique' =>
                'The category name has already been taken.',

            'image.image' =>
                'The uploaded file must be an image.',

            'image.mimes' =>
                'The image must be jpg, jpeg, png, or webp.',

            'image.max' =>
                'The image may not be larger than 5MB.',
        ];
    }
}