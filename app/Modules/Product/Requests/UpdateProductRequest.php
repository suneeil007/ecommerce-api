<?php

namespace App\Modules\Product\Requests;

use App\Modules\Attribute\Models\AttributeValue;
use App\Modules\Category\Models\Category;
use App\Modules\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare multipart/form-data values before validation.
     *
     * React sends options and variants as JSON strings because
     * the complete product form is submitted using FormData.
     *
     * Example:
     *
     * options = '[{"attribute_uuid":"..."}]'
     *
     * becomes:
     *
     * options = [
     *     [
     *         'attribute_uuid' => '...',
     *     ],
     * ]
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        /*
        |--------------------------------------------------------------------------
        | Decode Options JSON
        |--------------------------------------------------------------------------
        */

        if ($this->has('options')) {
            $options = $this->input('options');

            if (is_string($options)) {
                $decoded = json_decode($options, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['options'] = $decoded;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Decode Variants JSON
        |--------------------------------------------------------------------------
        */

        if ($this->has('variants')) {
            $variants = $this->input('variants');

            if (is_string($variants)) {
                $decoded = json_decode($variants, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['variants'] = $decoded;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Convert Category UUID -> Category ID
        |--------------------------------------------------------------------------
        |
        | React works with category UUIDs.
        |
        | Product table stores category_id as an integer.
        |
        | Example:
        |
        | React:
        | category_id = "4e62f7e0-..."
        |
        | Database:
        | category_id = 1
        |
        */

        if (
            $this->filled('category_id') &&
            is_string($this->input('category_id'))
        ) {
            $categoryUuid = $this->input('category_id');

            $categoryId = Category::where(
                'uuid',
                $categoryUuid
            )->value('id');

            if ($categoryId !== null) {
                $data['category_id'] = $categoryId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Merge Prepared Values
        |--------------------------------------------------------------------------
        */

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');

        $productId = Product::where(
            'uuid',
            $uuid
        )->value('id');

        return [
            /*
            |--------------------------------------------------------------------------
            | Basic Product Information
            |--------------------------------------------------------------------------
            */

            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'display_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique(
                    'products',
                    'slug'
                )->ignore($productId),
            ],

            'product_type' => [
                'sometimes',
                'string',
                Rule::in([
                    'simple',
                    'variable',
                    'digital',
                    'downloadable',
                ]),
            ],

            'short_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | SKU / Pricing
            |--------------------------------------------------------------------------
            */

            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique(
                    'products',
                    'sku'
                )->ignore($productId),
            ],

            'price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'compare_at_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'cost_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            'stock' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'low_stock_threshold' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'weight' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'draft',
                    'published',
                    'archived',
                ]),
            ],

            'is_featured' => [
                'sometimes',
                'boolean',
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

            /*
            |--------------------------------------------------------------------------
            | Product Attribute Values
            |--------------------------------------------------------------------------
            */

            'attribute_values' => [
                'sometimes',
                'array',
            ],

            'attribute_values.*' => [
                'integer',
                'exists:attribute_values,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Product Options
            |--------------------------------------------------------------------------
            |
            | Missing:
            |   Leave existing options unchanged.
            |
            | []:
            |   Remove all options.
            |
            | [...] :
            |   Synchronize options.
            |
            */

            'options' => [
                'sometimes',
                'array',
            ],

            'options.*.attribute_uuid' => [
                'required',
                'uuid',
                'exists:attributes,uuid',
            ],

            'options.*.sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'options.*.values' => [
                'required',
                'array',
                'min:1',
            ],

            'options.*.values.*.attribute_value_uuid' => [
                'required',
                'uuid',
                'exists:attribute_values,uuid',
            ],

            'options.*.values.*.sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Variants
            |--------------------------------------------------------------------------
            */

            'variants' => [
                'sometimes',
                'array',
            ],

            'variants.*.id' => [
                'nullable',
                'integer',
            ],

            'variants.*.uuid' => [
                'nullable',
                'uuid',
            ],

            'variants.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'variants.*.sku' => [
                'required',
                'string',
                'max:255',
                'distinct',
            ],

            'variants.*.barcode' => [
                'nullable',
                'string',
                'max:255',
                'distinct',
            ],

            'variants.*.price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'variants.*.compare_at_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'variants.*.cost_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'variants.*.stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'variants.*.low_stock_threshold' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'variants.*.weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Variant Image
            |--------------------------------------------------------------------------
            |
            | Existing image path:
            |   string
            |
            | New uploaded image:
            |   UploadedFile
            |
            */

            'variants.*.image' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------
                    | Uploaded image
                    |--------------------------------------------------------------
                    */

                    if (
                        $value instanceof \Illuminate\Http\UploadedFile
                    ) {
                        if (!$value->isValid()) {
                            $fail(
                                'The uploaded variant image is invalid.'
                            );

                            return;
                        }

                        $allowedMimeTypes = [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ];

                        if (
                            !in_array(
                                $value->getMimeType(),
                                $allowedMimeTypes,
                                true
                            )
                        ) {
                            $fail(
                                'Each variant image must be a JPG, JPEG, PNG, or WEBP image.'
                            );

                            return;
                        }

                        if ($value->getSize() > 5120 * 1024) {
                            $fail(
                                'Each variant image must not exceed 5MB.'
                            );
                        }

                        return;
                    }

                    /*
                    |--------------------------------------------------------------
                    | Existing image path
                    |--------------------------------------------------------------
                    */

                    if (!is_string($value)) {
                        $fail(
                            'The variant image must be an uploaded file or existing image path.'
                        );
                    }
                },
            ],

            'variants.*.is_active' => [
                'sometimes',
                'boolean',
            ],

            'variants.*.sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Variant Option Selections
            |--------------------------------------------------------------------------
            */

            'variants.*.option_selections' => [
                'sometimes',
                'array',
            ],

            'variants.*.option_selections.*.attribute_uuid' => [
                'required',
                'uuid',
                'exists:attributes,uuid',
            ],

            'variants.*.option_selections.*.attribute_value_uuid' => [
                'required',
                'uuid',
                'exists:attribute_values,uuid',
            ],

            /*
            |--------------------------------------------------------------------------
            | Legacy Option Value IDs
            |--------------------------------------------------------------------------
            |
            | Kept temporarily for backward compatibility.
            |
            */

            'variants.*.option_value_ids' => [
                'sometimes',
                'array',
            ],

            'variants.*.option_value_ids.*' => [
                'integer',
                'exists:product_option_values,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Product Images
            |--------------------------------------------------------------------------
            */

            'images' => [
                'sometimes',
                'array',
            ],

            'images.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    /**
     * Additional validation after the normal Laravel rules.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $options = $this->input('options', []);
            $variants = $this->input('variants', []);

            /*
            |--------------------------------------------------------------------------
            | Validate Option Values Belong To Attribute
            |--------------------------------------------------------------------------
            */

            foreach ($options as $optionIndex => $option) {
                $attributeUuid =
                    $option['attribute_uuid'] ?? null;

                if (!$attributeUuid) {
                    continue;
                }

                $attributeValueUuids = collect(
                    $option['values'] ?? []
                )
                    ->pluck('attribute_value_uuid')
                    ->filter()
                    ->values();

                if ($attributeValueUuids->isEmpty()) {
                    continue;
                }

                $validCount = AttributeValue::query()
                    ->whereIn(
                        'uuid',
                        $attributeValueUuids
                    )
                    ->whereHas(
                        'attribute',
                        function ($query) use ($attributeUuid) {
                            $query->where(
                                'uuid',
                                $attributeUuid
                            );
                        }
                    )
                    ->count();

                if (
                    $validCount !==
                    $attributeValueUuids->count()
                ) {
                    $validator->errors()->add(
                        "options.$optionIndex.values",
                        'One or more attribute values do not belong to the selected attribute.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Variant UUID Selections
            |--------------------------------------------------------------------------
            */

            foreach ($variants as $variantIndex => $variant) {
                $selections =
                    $variant['option_selections'] ?? [];

                $attributeUuids = [];

                foreach (
                    $selections
                    as $selectionIndex => $selection
                ) {
                    $attributeUuid =
                        $selection['attribute_uuid'] ?? null;

                    $attributeValueUuid =
                        $selection['attribute_value_uuid'] ?? null;

                    if (
                        !$attributeUuid ||
                        !$attributeValueUuid
                    ) {
                        continue;
                    }

                    $attributeUuids[] =
                        $attributeUuid;

                    $belongsToAttribute =
                        AttributeValue::query()
                            ->where(
                                'uuid',
                                $attributeValueUuid
                            )
                            ->whereHas(
                                'attribute',
                                function ($query) use (
                                    $attributeUuid
                                ) {
                                    $query->where(
                                        'uuid',
                                        $attributeUuid
                                    );
                                }
                            )
                            ->exists();

                    if (!$belongsToAttribute) {
                        $validator->errors()->add(
                            "variants.$variantIndex.option_selections.$selectionIndex.attribute_value_uuid",
                            'The selected attribute value does not belong to the selected attribute.'
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | One Value Per Attribute
                |--------------------------------------------------------------------------
                */

                if (
                    count($attributeUuids) !==
                    count(array_unique($attributeUuids))
                ) {
                    $validator->errors()->add(
                        "variants.$variantIndex.option_selections",
                        'A variant cannot contain multiple values from the same attribute.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'sku.unique' =>
                'This SKU is already in use.',

            'slug.unique' =>
                'This slug is already in use.',

            'options.*.attribute_uuid.exists' =>
                'The selected attribute does not exist.',

            'options.*.values.*.attribute_value_uuid.exists' =>
                'The selected attribute value does not exist.',

            'variants.*.image.image' =>
                'Each variant image must be a valid image.',

            'variants.*.image.max' =>
                'Each variant image must not exceed 5MB.',

            'images.*.image' =>
                'Each product image must be a valid image.',

            'images.*.max' =>
                'Each product image must not exceed 5MB.',
        ];
    }
}

