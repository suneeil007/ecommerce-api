<?php

namespace App\Modules\Product\Requests;

use App\Modules\Attribute\Models\AttributeValue;
use App\Modules\Category\Models\Category;
use App\Modules\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Request Data
    |--------------------------------------------------------------------------
    |
    | Product creation uses multipart/form-data because product images
    | are uploaded together with the product information.
    |
    | Therefore options and variants arrive from React as JSON strings.
    |
    | Example:
    |
    | options  = "[{...}, {...}]"
    | variants = "[{...}, {...}]"
    |
    | Convert them into PHP arrays before validation.
    |
    | React also sends category_id as a category UUID, so it is converted
    | to the integer category ID here (same as UpdateProductRequest).
    |
    */

    protected function prepareForValidation(): void
    {
        $data = [];

        /*
        |--------------------------------------------------------------------------
        | Product Options
        |--------------------------------------------------------------------------
        */

        if (is_string($this->input('options'))) {
            $options = json_decode(
                $this->input('options'),
                true
            );

            $data['options'] = is_array($options)
                ? $options
                : [];
        }

        /*
        |--------------------------------------------------------------------------
        | Product Variants
        |--------------------------------------------------------------------------
        */

        if (is_string($this->input('variants'))) {
            $variants = json_decode(
                $this->input('variants'),
                true
            );

            $data['variants'] = is_array($variants)
                ? $variants
                : [];
        }

        /*
        |--------------------------------------------------------------------------
        | Variant Images
        |--------------------------------------------------------------------------
        |
        | Files cannot be sent inside the variants JSON, so React uploads them
        | as variant_images[index]. Attach each one back onto its variant so
        | variants.*.image validates as a real uploaded file.
        |
        */

        if (
            isset($data['variants']) &&
            is_array($data['variants'])
        ) {
            foreach ($data['variants'] as $index => $variant) {
                if (!is_array($variant)) {
                    continue;
                }

                $data['variants'][$index]['image'] =
                    $this->file("variant_images.$index");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Convert Category UUID -> Category ID
        |--------------------------------------------------------------------------
        |
        | React:
        | category_id = "4e62f7e0-..."
        |
        | Database:
        | category_id = 1
        |
        | If the UUID does not match any category, the original value is kept
        | and the "integer" rule rejects it.
        |
        */

        if (
            $this->filled('category_id') &&
            is_string($this->input('category_id'))
        ) {
            $categoryId = Category::where(
                'uuid',
                $this->input('category_id')
            )->value('id');

            if ($categoryId !== null) {
                $data['category_id'] = $categoryId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Display Name / SKU Fallbacks
        |--------------------------------------------------------------------------
        |
        | The form fills these from the product name, but they may arrive
        | empty (for example if the user cleared them).
        |
        */

        $name = trim((string) $this->input('name'));

        if ($name !== '') {
            if (!$this->filled('display_name')) {
                $data['display_name'] = $name;
            }

            if (!$this->filled('sku')) {
                $data['sku'] = $this->generateUniqueSku($name);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Variable Product Stock / Price
        |--------------------------------------------------------------------------
        |
        | For variable products the Stock input is disabled because stock is
        | managed by variants, so it arrives empty.
        |
        | Stock  = total stock of the active variants.
        | Price  = lowest price among the active variants (only when the
        |          product price itself was left empty).
        |
        */

        if ($this->input('product_type') === 'variable') {
            $variants = $data['variants']
                ?? $this->input('variants', []);

            $activeVariants = collect(
                is_array($variants) ? $variants : []
            )->filter(
                fn ($variant) =>
                    is_array($variant) &&
                    ($variant['is_active'] ?? true) &&
                    !($variant['manually_removed'] ?? false)
            );

            if (!$this->filled('stock')) {
                $data['stock'] = $activeVariants->sum(
                    fn ($variant) => (int) ($variant['stock'] ?? 0)
                );
            }

            if (!$this->filled('price')) {
                $lowestPrice = $activeVariants
                    ->pluck('price')
                    ->filter(
                        fn ($price) =>
                            $price !== null &&
                            $price !== '' &&
                            is_numeric($price)
                    )
                    ->min();

                if ($lowestPrice !== null) {
                    $data['price'] = $lowestPrice;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Merge Converted Data
        |--------------------------------------------------------------------------
        */

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unique SKU
    |--------------------------------------------------------------------------
    |
    | Example: "iPhone 15 Pro" -> "IPHONE-15-PRO-K3F9A"
    |
    */

    private function generateUniqueSku(string $name): string
    {
        $base = Str::upper(
            rtrim(
                Str::limit(
                    Str::slug($name),
                    20,
                    ''
                ),
                '-'
            )
        );

        if ($base === '') {
            $base = 'PRD';
        }

        do {
            $sku = $base . '-' . Str::upper(Str::random(5));
        } while (
            Product::where('sku', $sku)->exists()
        );

        return $sku;
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Product Information
            |--------------------------------------------------------------------------
            */

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',

                function ($attribute, $value, $fail) {
                    $slug = Str::slug($value);

                    if (
                        Product::where(
                            'slug',
                            $slug
                        )->exists()
                    ) {
                        $fail(
                            'A product with this name already exists.'
                        );
                    }
                },
            ],

            'display_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,slug',
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
                'nullable',
                'string',
                'max:1000',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | SKU / Pricing
            |--------------------------------------------------------------------------
            */

            'sku' => [
                'required',
                'string',
                'max:255',
                'unique:products,sku',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'compare_at_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:price',
            ],

            'cost_price' => [
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
                'required',
                'integer',
                'min:0',
            ],

            'low_stock_threshold' => [
                'required',
                'integer',
                'min:0',
            ],

            'weight' => [
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
                'required',
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
            |
            | General product attributes.
            |
            | Example:
            |
            | attribute_values: [1, 2, 5]
            |
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
            | Example:
            |
            | options: [
            |     [
            |         'attribute_uuid' => '...',
            |         'sort_order' => 0,
            |         'values' => [
            |             [
            |                 'attribute_value_uuid' => '...',
            |                 'sort_order' => 0,
            |             ],
            |         ],
            |     ],
            | ]
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
            | Product Variants
            |--------------------------------------------------------------------------
            */

            'variants' => [
                'sometimes',
                'array',
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
            */

            'variants.*.image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
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
            | Variant -> Option Selections
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | option_selections: [
            |     [
            |         'attribute_uuid' => '...',
            |         'attribute_value_uuid' => '...',
            |     ],
            | ]
            |
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

    /*
    |--------------------------------------------------------------------------
    | Additional Validation
    |--------------------------------------------------------------------------
    */

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            /*
            |--------------------------------------------------------------------------
            | Product Options
            |--------------------------------------------------------------------------
            */

            $options = $this->input(
                'options',
                []
            );

            /*
            |--------------------------------------------------------------------------
            | Product Variants
            |--------------------------------------------------------------------------
            */

            $variants = $this->input(
                'variants',
                []
            );

            /*
            |--------------------------------------------------------------------------
            | Safety Check
            |--------------------------------------------------------------------------
            |
            | prepareForValidation() normally guarantees arrays.
            | These checks prevent foreach() errors if malformed input
            | somehow reaches this point.
            |
            */

            if (!is_array($options)) {
                $options = [];
            }

            if (!is_array($variants)) {
                $variants = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Option Attribute / Attribute Value Relationship
            |--------------------------------------------------------------------------
            |
            | Prevents:
            |
            | Color -> 256 GB
            |
            | when 256 GB belongs to Storage instead of Color.
            |
            */

            foreach (
                $options
                as $optionIndex => $option
            ) {

                if (!is_array($option)) {
                    continue;
                }

                $attributeUuid =
                    $option['attribute_uuid'] ?? null;

                if (!$attributeUuid) {
                    continue;
                }

                $attributeValueUuids = collect(
                    $option['values'] ?? []
                )
                    ->filter(
                        fn ($value) => is_array($value)
                    )
                    ->pluck(
                        'attribute_value_uuid'
                    )
                    ->filter()
                    ->values();

                if (
                    $attributeValueUuids->isEmpty()
                ) {
                    continue;
                }

                $validCount =
                    AttributeValue::query()
                        ->where(
                            'attribute_id',
                            function ($query) use (
                                $attributeUuid
                            ) {
                                $query
                                    ->select('id')
                                    ->from('attributes')
                                    ->where(
                                        'uuid',
                                        $attributeUuid
                                    )
                                    ->limit(1);
                            }
                        )
                        ->whereIn(
                            'uuid',
                            $attributeValueUuids
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
            | Validate Variant Selections
            |--------------------------------------------------------------------------
            */

            foreach (
                $variants
                as $variantIndex => $variant
            ) {

                if (!is_array($variant)) {
                    continue;
                }

                $selections =
                    $variant['option_selections']
                    ?? [];

                if (!is_array($selections)) {
                    $selections = [];
                }

                foreach (
                    $selections
                    as $selectionIndex => $selection
                ) {

                    if (!is_array($selection)) {
                        continue;
                    }

                    $attributeUuid =
                        $selection['attribute_uuid']
                        ?? null;

                    $attributeValueUuid =
                        $selection['attribute_value_uuid']
                        ?? null;

                    if (
                        !$attributeUuid ||
                        !$attributeValueUuid
                    ) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Check Attribute / Attribute Value Relationship
                    |--------------------------------------------------------------------------
                    */

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
                | Prevent Same Attribute Twice In One Variant
                |--------------------------------------------------------------------------
                */

                $attributeUuids = collect(
                    $selections
                )
                    ->filter(
                        fn ($selection) =>
                            is_array($selection)
                    )
                    ->pluck(
                        'attribute_uuid'
                    )
                    ->filter()
                    ->values();

                if (
                    $attributeUuids->count() !==
                    $attributeUuids->unique()->count()
                ) {
                    $validator->errors()->add(
                        "variants.$variantIndex.option_selections",
                        'A variant cannot contain multiple values from the same attribute.'
                    );
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category_id.exists' =>
                'The selected category does not exist.',

            /*
            |--------------------------------------------------------------------------
            | SKU / Slug
            |--------------------------------------------------------------------------
            */

            'sku.unique' =>
                'This SKU is already in use.',

            'slug.unique' =>
                'This slug is already in use.',

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'price.min' =>
                'Product price cannot be negative.',

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            'stock.min' =>
                'Stock cannot be negative.',

            /*
            |--------------------------------------------------------------------------
            | Product Options
            |--------------------------------------------------------------------------
            */

            'options.*.attribute_uuid.exists' =>
                'The selected attribute does not exist.',

            'options.*.values.*.attribute_value_uuid.exists' =>
                'The selected attribute value does not exist.',

            /*
            |--------------------------------------------------------------------------
            | Variant Images
            |--------------------------------------------------------------------------
            */

            'variants.*.image.image' =>
                'Each variant image must be a valid image.',

            'variants.*.image.max' =>
                'Each variant image must not exceed 5MB.',

            /*
            |--------------------------------------------------------------------------
            | Product Images
            |--------------------------------------------------------------------------
            */

            'images.*.image' =>
                'Each product image must be a valid image.',

            'images.*.max' =>
                'Each product image must not exceed 5MB.',
        ];
    }
}