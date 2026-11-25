<?php

namespace App\Modules\Product\Services;

use App\Modules\Attribute\Models\Attribute;
use App\Modules\Attribute\Models\AttributeValue;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductImage;
use App\Modules\Product\Models\ProductOption;
use App\Modules\Product\Models\ProductOptionValue;
use App\Modules\Product\Models\ProductVariant;
use App\Modules\Product\Support\DescriptionImageCleaner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductService
{
    /*
    |--------------------------------------------------------------------------
    | Create Product
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            $images = $data['images'] ?? [];
            $attributeValueIds = $data['attribute_values'] ?? [];
            $options = $data['options'] ?? [];
            $variants = $data['variants'] ?? [];

            unset(
                $data['images'],
                $data['attribute_values'],
                $data['options'],
                $data['variants']
            );

            /*
            |--------------------------------------------------------------------------
            | Create Product
            |--------------------------------------------------------------------------
            */

            $product = Product::create($data);

            /*
            |--------------------------------------------------------------------------
            | Product Attribute Values
            |--------------------------------------------------------------------------
            */

            $this->syncAttributeValues(
                $product,
                $attributeValueIds
            );

            /*
            |--------------------------------------------------------------------------
            | Product Options
            |--------------------------------------------------------------------------
            |
            | Each product option now references a master Attribute.
            |
            | Example:
            |
            | ProductOption
            |     attribute_id -> Attribute
            |
            | ProductOptionValue
            |     attribute_value_id -> AttributeValue
            |
            */

            $optionIndexMap = $this->syncOptions(
                $product,
                $options
            );

            /*
            |--------------------------------------------------------------------------
            | Product Variants
            |--------------------------------------------------------------------------
            */

            $this->syncVariants(
                $product,
                $variants,
                $optionIndexMap
            );

            /*
            |--------------------------------------------------------------------------
            | Product Images
            |--------------------------------------------------------------------------
            */

            $this->syncImages(
                $product,
                $images
            );

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Product
            |--------------------------------------------------------------------------
            */

            return $product->fresh([
                'category',
                'images',
                'primaryImage',
                'attributeValues.attribute',
                'options.attribute',
                'options.values.attributeValue',
                'variants.optionValues.productOption.attribute',
                'variants.optionValues.attributeValue',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    |
    | Replace the whole existing update() method in ProductService with this.
    | Everything else in the class stays the same.
    |
    */

    public function update(Product $product, array $data): Product
    {
        $removedDescriptionImages = [];

        $updated = DB::transaction(function () use ($product, $data, &$removedDescriptionImages) {

            /*
            |--------------------------------------------------------------------------
            | Detect Nested Fields
            |--------------------------------------------------------------------------
            */

            $images = $data['images'] ?? null;
            $attributeValueIds = $data['attribute_values'] ?? null;
            $options = $data['options'] ?? null;
            $variants = $data['variants'] ?? null;

            unset(
                $data['images'],
                $data['attribute_values'],
                $data['options'],
                $data['variants']
            );

            /*
            |--------------------------------------------------------------------------
            | Update Product
            |--------------------------------------------------------------------------
            */

            if (!empty($data)) {

                $descriptionChanged = array_key_exists('description', $data);

                $oldDescriptionImages = $descriptionChanged
                    ? DescriptionImageCleaner::extract($product->description)
                    : [];

                $product->update($data);

                $newDescriptionImages = $descriptionChanged
                    ? DescriptionImageCleaner::extract($product->description)
                    : [];

                $removedDescriptionImages = array_values(array_diff(
                    $oldDescriptionImages,
                    $newDescriptionImages
                ));

                \Log::info('description cleanup', [
                    'changed' => $descriptionChanged,
                    'old' => $oldDescriptionImages,
                    'new' => $newDescriptionImages,
                    'removed' => $removedDescriptionImages,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Attribute Values
            |--------------------------------------------------------------------------
            |
            | omitted -> unchanged
            | []      -> clear
            | [...]   -> synchronize
            |
            */

            if ($attributeValueIds !== null) {

                $this->syncAttributeValues(
                    $product,
                    $attributeValueIds
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Options
            |--------------------------------------------------------------------------
            |
            | omitted -> unchanged
            | []      -> remove all
            | [...]   -> synchronize
            |
            */

            if ($options !== null) {

                $optionIndexMap = $this->syncOptions(
                    $product,
                    $options
                );

            } else {

                $optionIndexMap =
                    $this->buildOptionIndexMapFromExisting(
                        $product
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Variants
            |--------------------------------------------------------------------------
            */

            if ($variants !== null) {

                $this->syncVariants(
                    $product,
                    $variants,
                    $optionIndexMap
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Images
            |--------------------------------------------------------------------------
            */

            if ($images !== null) {

                $this->syncImages(
                    $product,
                    $images
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Product
            |--------------------------------------------------------------------------
            */

            return $product->fresh([
                'category',
                'images',
                'primaryImage',
                'attributeValues.attribute',
                'options.attribute',
                'options.values.attributeValue',
                'variants.optionValues.productOption.attribute',
                'variants.optionValues.attributeValue',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Delete Removed Description Images
        |--------------------------------------------------------------------------
        |
        | The transaction has committed at this point, so deleting the files
        | is safe: a rolled-back update never reaches this line.
        |
        */

        if (!empty($removedDescriptionImages)) {
            DescriptionImageCleaner::delete($removedDescriptionImages);
        }

        return $updated;
    }

    /*
    |--------------------------------------------------------------------------
    | Product Attribute Values
    |--------------------------------------------------------------------------
    */

    protected function syncAttributeValues(
        Product $product,
        array $attributeValueIds
    ): void {

        $attributeValueIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $attributeValueIds
                )
            )
        );

        if (empty($attributeValueIds)) {
            $product->attributeValues()->sync([]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Attribute Values
        |--------------------------------------------------------------------------
        */

        $validIds = AttributeValue::query()
            ->whereIn('id', $attributeValueIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (count($validIds) !== count($attributeValueIds)) {

            throw new InvalidArgumentException(
                'One or more product attribute values are invalid.'
            );
        }

        $product->attributeValues()->sync(
            $attributeValueIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Product Options
    |--------------------------------------------------------------------------
    |
    | New input:
    |
    | [
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
    | The ProductOption receives its information from Attribute.
    |
    */

    protected function syncOptions(
        Product $product,
        array $options
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Empty Array = Remove All Options
        |--------------------------------------------------------------------------
        */

        if (empty($options)) {

            $product->options()->delete();

            return [];
        }

        $options = array_values($options);

        $existingOptionIds = [];

        /*
        |----------------------------------------------------------------------
        | Index Map
        |----------------------------------------------------------------------
        |
        | Returned structure:
        |
        | [
        |     0 => [
        |         'id' => 10,
        |         'attribute_id' => 3,
        |         'attribute_uuid' => '...',
        |         'values' => [
        |             0 => 20,
        |             1 => 21,
        |         ],
        |     ],
        | ]
        |
        */

        $optionIndexMap = [];

        foreach ($options as $optionIndex => $optionData) {

            $attributeUuid =
                $optionData['attribute_uuid']
                ?? null;

            if (!$attributeUuid) {

                throw new InvalidArgumentException(
                    'Each product option must specify an attribute_uuid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Find Master Attribute
            |--------------------------------------------------------------------------
            */

            $attribute = Attribute::query()
                ->where('uuid', $attributeUuid)
                ->first();

            if (!$attribute) {

                throw new InvalidArgumentException(
                    "Attribute '{$attributeUuid}' was not found."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Optional Active Check
            |--------------------------------------------------------------------------
            */

            if (
                isset($attribute->is_active)
                &&
                !$attribute->is_active
            ) {

                $attributeName =
                    $attribute->display_name
                    ?? $attribute->name;

                throw new InvalidArgumentException(
                    "Attribute '{$attributeName}' is inactive."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Product Option
            |--------------------------------------------------------------------------
            |
            | The master Attribute determines:
            |
            | name
            | slug
            | display_name
            |
            */

            $option = ProductOption::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_id' => $attribute->id,
                ],
                [
                    'name' =>
                        $attribute->name,

                    'display_name' =>
                        $attribute->display_name
                        ?? $attribute->name,

                    'sort_order' =>
                        $optionData['sort_order']
                        ?? $optionIndex,
                ]
            );

            $existingOptionIds[] = $option->id;

            /*
            |--------------------------------------------------------------------------
            | Product Option Values
            |--------------------------------------------------------------------------
            */

            $valueIndexMap = $this->syncOptionValues(
                $option,
                $attribute,
                $optionData['values'] ?? []
            );

            $optionIndexMap[$optionIndex] = [
                'id' =>
                    $option->id,

                'attribute_id' =>
                    $attribute->id,

                'attribute_uuid' =>
                    $attribute->uuid,

                'values' =>
                    $valueIndexMap,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Removed Options
        |--------------------------------------------------------------------------
        */

        ProductOption::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $existingOptionIds)
            ->delete();

        return $optionIndexMap;
    }

    /*
    |--------------------------------------------------------------------------
    | Product Option Values
    |--------------------------------------------------------------------------
    |
    | Each ProductOptionValue references an AttributeValue.
    |
    */

    protected function syncOptionValues(
        ProductOption $option,
        Attribute $attribute,
        array $values
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Empty Values
        |--------------------------------------------------------------------------
        */

        if (empty($values)) {

            $option->values()->delete();

            return [];
        }

        $values = array_values($values);

        $existingValueIds = [];
        $valueIndexMap = [];

        foreach ($values as $valueIndex => $valueData) {

            $attributeValueUuid =
                $valueData['attribute_value_uuid']
                ?? null;

            if (!$attributeValueUuid) {

                throw new InvalidArgumentException(
                    "Each value for attribute '{$attribute->name}' must specify an attribute_value_uuid."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Find Master Attribute Value
            |--------------------------------------------------------------------------
            */

            $attributeValue = AttributeValue::query()
                ->where('uuid', $attributeValueUuid)
                ->where('attribute_id', $attribute->id)
                ->first();

            if (!$attributeValue) {

                throw new InvalidArgumentException(
                    "Attribute value '{$attributeValueUuid}' does not belong to attribute '{$attribute->name}'."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Active Attribute Value
            |--------------------------------------------------------------------------
            */

            if (
                isset($attributeValue->is_active)
                &&
                !$attributeValue->is_active
            ) {

                throw new InvalidArgumentException(
                    "Attribute value '{$attributeValue->value}' is inactive."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create / Update Product Option Value
            |--------------------------------------------------------------------------
            |
            | The master AttributeValue determines:
            |
            | value
            | slug
            | display_value
            |
            */

            $optionValue = ProductOptionValue::updateOrCreate(
                [
                    'product_option_id' =>
                        $option->id,

                    'attribute_value_id' =>
                        $attributeValue->id,
                ],
                [
                    'value' =>
                        $attributeValue->value,

                    'display_value' =>
                        $attributeValue->display_value
                        ?? $attributeValue->value,

                    'sort_order' =>
                        $valueData['sort_order']
                        ?? $valueIndex,
                ]
            );

            $existingValueIds[] =
                $optionValue->id;

            $valueIndexMap[$valueIndex] =
                $optionValue->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Removed Values
        |--------------------------------------------------------------------------
        */

        $option->values()
            ->whereNotIn(
                'id',
                $existingValueIds
            )
            ->delete();

        return $valueIndexMap;
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Option Index Map
    |--------------------------------------------------------------------------
    |
    | Used when an UPDATE request doesn't contain options but does contain
    | variants using the old option_index/value_index structure.
    |
    */

    protected function buildOptionIndexMapFromExisting(
        Product $product
    ): array {

        $options = $product->options()
            ->with([
                'attribute',
                'values',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $optionIndexMap = [];

        foreach ($options as $optionIndex => $option) {

            $sortedValues = $option->values
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();

            $valueIndexMap = [];

            foreach ($sortedValues as $valueIndex => $value) {

                $valueIndexMap[$valueIndex] =
                    $value->id;
            }

            $optionIndexMap[$optionIndex] = [
                'id' =>
                    $option->id,

                'attribute_id' =>
                    $option->attribute_id,

                'attribute_uuid' =>
                    $option->attribute?->uuid,

                'values' =>
                    $valueIndexMap,
            ];
        }

        return $optionIndexMap;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Variant Selections
    |--------------------------------------------------------------------------
    |
    | Supports:
    |
    | NEW:
    |
    | [
    |     [
    |         'attribute_uuid' => '...',
    |         'attribute_value_uuid' => '...',
    |     ]
    | ]
    |
    | LEGACY:
    |
    | [
    |     [
    |         'option_index' => 0,
    |         'value_index' => 1,
    |     ]
    | ]
    |
    */

    protected function resolveOptionSelections(
        Product $product,
        array $optionIndexMap,
        array $selections
    ): array {

        $resolved = [];

        foreach ($selections as $selection) {

            /*
            |--------------------------------------------------------------------------
            | New UUID-Based Selection
            |--------------------------------------------------------------------------
            */

            if (
                isset($selection['attribute_uuid'])
                ||
                isset($selection['attribute_value_uuid'])
            ) {

                $attributeUuid =
                    $selection['attribute_uuid']
                    ?? null;

                $attributeValueUuid =
                    $selection['attribute_value_uuid']
                    ?? null;

                if (
                    !$attributeUuid
                    ||
                    !$attributeValueUuid
                ) {

                    throw new InvalidArgumentException(
                        'Variant option selection requires attribute_uuid and attribute_value_uuid.'
                    );
                }

                /*
                |------------------------------------------------------------------
                | Find Product Option
                |------------------------------------------------------------------
                */

                $option = $product->options()
                    ->whereHas(
                        'attribute',
                        function ($query) use ($attributeUuid) {
                            $query->where(
                                'uuid',
                                $attributeUuid
                            );
                        }
                    )
                    ->first();

                if (!$option) {

                    throw new InvalidArgumentException(
                        "Variant references an attribute that is not configured for this product."
                    );
                }

                /*
                |------------------------------------------------------------------
                | Find Product Option Value
                |------------------------------------------------------------------
                */

                $optionValue =
                    ProductOptionValue::query()
                        ->where(
                            'product_option_id',
                            $option->id
                        )
                        ->whereHas(
                            'attributeValue',
                            function ($query) use ($attributeValueUuid) {
                                $query->where(
                                    'uuid',
                                    $attributeValueUuid
                                );
                            }
                        )
                        ->first();

                if (!$optionValue) {

                    throw new InvalidArgumentException(
                        "Variant references an attribute value that is not configured for this product."
                    );
                }

                $resolved[] =
                    $optionValue->id;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Legacy Index-Based Selection
            |--------------------------------------------------------------------------
            */

            $optionIndex =
                $selection['option_index']
                ?? null;

            $valueIndex =
                $selection['value_index']
                ?? null;

            if (
                $optionIndex === null
                ||
                $valueIndex === null
                ||
                !isset(
                    $optionIndexMap[$optionIndex]['values'][$valueIndex]
                )
            ) {

                throw new InvalidArgumentException(
                    'One or more variant option selections do not match any submitted option.'
                );
            }

            $resolved[] =
                $optionIndexMap[$optionIndex]['values'][$valueIndex];
        }

        return array_values(
            array_unique($resolved)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Variant Combinations
    |--------------------------------------------------------------------------
    */

    protected function validateVariantCombinations(
        Product $product,
        array $variants
    ): void {

        $combinations = [];

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Combinations Inside Request
        |--------------------------------------------------------------------------
        */

        foreach ($variants as $index => $variantData) {

            $optionValueIds =
                $variantData['option_value_ids']
                ?? [];

            $optionValueIds = array_values(
                array_unique(
                    array_map(
                        'intval',
                        $optionValueIds
                    )
                )
            );

            if (empty($optionValueIds)) {

                $combinationKey = 'no-options';

            } else {

                sort($optionValueIds);

                $combinationKey =
                    implode(
                        '-',
                        $optionValueIds
                    );
            }

            if (isset($combinations[$combinationKey])) {

                $duplicateIndex =
                    $combinations[$combinationKey];

                throw new InvalidArgumentException(
                    'Duplicate variant combination detected between variants '
                    . ($duplicateIndex + 1)
                    . ' and '
                    . ($index + 1)
                    . '.'
                );
            }

            $combinations[$combinationKey] =
                $index;
        }

        /*
        |--------------------------------------------------------------------------
        | Check Existing Database Variants
        |--------------------------------------------------------------------------
        */

        $existingVariants = $product->variants()
            ->with('optionValues:id')
            ->get();

        foreach ($existingVariants as $existingVariant) {

            $existingIds = $existingVariant
                ->optionValues
                ->pluck('id')
                ->map(
                    fn ($id) => (int) $id
                )
                ->sort()
                ->values()
                ->toArray();

            $existingKey = empty($existingIds)
                ? 'no-options'
                : implode(
                    '-',
                    $existingIds
                );

            foreach ($variants as $index => $variantData) {

                $incomingIds =
                    $variantData['option_value_ids']
                    ?? [];

                $incomingIds = array_values(
                    array_unique(
                        array_map(
                            'intval',
                            $incomingIds
                        )
                    )
                );

                sort($incomingIds);

                $incomingKey =
                    empty($incomingIds)
                    ? 'no-options'
                    : implode(
                        '-',
                        $incomingIds
                    );

                $incomingVariantId =
                    $variantData['id']
                    ?? $variantData['uuid']
                    ?? null;

                $sameVariant = false;

                if ($incomingVariantId !== null) {

                    $sameVariant =
                        (string) $existingVariant->id
                        ===
                        (string) $incomingVariantId
                        ||
                        (string) $existingVariant->uuid
                        ===
                        (string) $incomingVariantId;
                }

                if (
                    !$sameVariant
                    &&
                    $existingKey === $incomingKey
                ) {

                    throw new InvalidArgumentException(
                        'Duplicate variant combination already exists: '
                        . $this->getVariantCombinationName(
                            $existingVariant
                        )
                        . '.'
                    );
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Variant Combination Name
    |--------------------------------------------------------------------------
    */

    protected function getVariantCombinationName(
        ProductVariant $variant
    ): string {

        $variant->loadMissing([
            'optionValues.attributeValue',
        ]);

        $names = $variant
            ->optionValues
            ->map(
                fn ($value) =>
                    $value->attributeValue?->display_value
                    ??
                    $value->attributeValue?->value
                    ??
                    $value->display_value
                    ??
                    $value->value
            )
            ->filter()
            ->values()
            ->toArray();

        if (empty($names)) {

            return $variant->name
                ?? 'No option values';
        }

        return implode(
            ' + ',
            $names
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Variants
    |--------------------------------------------------------------------------
    */

    protected function syncVariants(
        Product $product,
        array $variants,
        array $optionIndexMap = []
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Empty Array = Remove All Variants
        |--------------------------------------------------------------------------
        */

        if (empty($variants)) {

            $product->variants()->delete();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Variant Selections
        |--------------------------------------------------------------------------
        */

        foreach ($variants as $i => &$variantData) {

            $selections =
                $variantData['option_selections']
                ?? null;

            if ($selections !== null) {

                $variantData['option_value_ids'] =
                    $this->resolveOptionSelections(
                        $product,
                        $optionIndexMap,
                        $selections
                    );
            }
        }

        unset($variantData);

        /*
        |--------------------------------------------------------------------------
        | Validate Variant Combinations
        |--------------------------------------------------------------------------
        */

        $this->validateVariantCombinations(
            $product,
            $variants
        );

        $existingIds = [];

        /*
        |--------------------------------------------------------------------------
        | Create / Update Variants
        |--------------------------------------------------------------------------
        */

        foreach ($variants as $variantData) {

            /*
            |--------------------------------------------------------------------------
            | Variant Image
            |--------------------------------------------------------------------------
            */

            $variantImagePath = null;

            if (
                isset($variantData['image'])
                &&
                $variantData['image']
                    instanceof UploadedFile
            ) {

                $variantImagePath =
                    $this->storeVariantImage(
                        $variantData['image']
                    );

            } elseif (
                isset($variantData['image'])
                &&
                is_string($variantData['image'])
                &&
                $variantData['image'] !== ''
            ) {

                $variantImagePath =
                    $variantData['image'];
            }

            /*
            |--------------------------------------------------------------------------
            | Existing Variant
            |--------------------------------------------------------------------------
            */

            $existingVariant = null;

            if (!empty($variantData['uuid'])) {

                $existingVariant =
                    ProductVariant::query()
                        ->where(
                            'product_id',
                            $product->id
                        )
                        ->where(
                            'uuid',
                            $variantData['uuid']
                        )
                        ->first();
            }

            if (
                !$existingVariant
                &&
                !empty($variantData['id'])
            ) {

                $existingVariant =
                    ProductVariant::query()
                        ->where(
                            'product_id',
                            $product->id
                        )
                        ->where(
                            'id',
                            $variantData['id']
                        )
                        ->first();
            }

            /*
            |--------------------------------------------------------------------------
            | Existing Image Preservation
            |--------------------------------------------------------------------------
            */

            if (
                $variantImagePath === null
                &&
                $existingVariant
            ) {

                $variantImagePath =
                    $existingVariant->image;
            }

            /*
            |--------------------------------------------------------------------------
            | SKU
            |--------------------------------------------------------------------------
            */

            if (empty($variantData['sku'])) {

                throw new InvalidArgumentException(
                    'Each product variant must have a SKU.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create / Update Variant
            |--------------------------------------------------------------------------
            */

            if ($existingVariant) {

                $variant = $existingVariant;

                $variant->update([
                    'name' =>
                        $variantData['name']
                        ?? null,

                    'sku' =>
                        $variantData['sku'],

                    'barcode' =>
                        $variantData['barcode']
                        ?? null,

                    'price' =>
                        $variantData['price']
                        ?? null,

                    'compare_at_price' =>
                        $variantData['compare_at_price']
                        ?? null,

                    'cost_price' =>
                        $variantData['cost_price']
                        ?? null,

                    'stock' =>
                        $variantData['stock']
                        ?? 0,

                    'low_stock_threshold' =>
                        $variantData['low_stock_threshold']
                        ?? 0,

                    'weight' =>
                        $variantData['weight']
                        ?? null,

                    'image' =>
                        $variantImagePath,

                    'is_active' =>
                        $variantData['is_active']
                        ?? true,

                    'sort_order' =>
                        $variantData['sort_order']
                        ?? 0,
                ]);

            } else {

                $variant = ProductVariant::create([
                    'product_id' =>
                        $product->id,

                    'name' =>
                        $variantData['name']
                        ?? null,

                    'sku' =>
                        $variantData['sku'],

                    'barcode' =>
                        $variantData['barcode']
                        ?? null,

                    'price' =>
                        $variantData['price']
                        ?? null,

                    'compare_at_price' =>
                        $variantData['compare_at_price']
                        ?? null,

                    'cost_price' =>
                        $variantData['cost_price']
                        ?? null,

                    'stock' =>
                        $variantData['stock']
                        ?? 0,

                    'low_stock_threshold' =>
                        $variantData['low_stock_threshold']
                        ?? 0,

                    'weight' =>
                        $variantData['weight']
                        ?? null,

                    'image' =>
                        $variantImagePath,

                    'is_active' =>
                        $variantData['is_active']
                        ?? true,

                    'sort_order' =>
                        $variantData['sort_order']
                        ?? 0,
                ]);
            }

            $existingIds[] =
                $variant->id;

            /*
            |--------------------------------------------------------------------------
            | Variant Option Values
            |--------------------------------------------------------------------------
            */

            $this->syncVariantOptionValues(
                $variant,
                $variantData['option_value_ids']
                ?? []
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Removed Variants
        |--------------------------------------------------------------------------
        */

        ProductVariant::query()
            ->where(
                'product_id',
                $product->id
            )
            ->whereNotIn(
                'id',
                $existingIds
            )
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Variant Image
    |--------------------------------------------------------------------------
    */

    protected function storeVariantImage(
        UploadedFile $file
    ): string {

        $filename =
            Str::slug(
                pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                )
            )
            . '-'
            . Str::random(20)
            . '.'
            . $file->getClientOriginalExtension();

        $directory =
            public_path(
                'products/variants'
            );

        if (!is_dir($directory)) {

            mkdir(
                $directory,
                0755,
                true
            );
        }

        $file->move(
            $directory,
            $filename
        );

        return 'products/variants/'
            . $filename;
    }

    /*
    |--------------------------------------------------------------------------
    | Variant Option Values
    |--------------------------------------------------------------------------
    */

    protected function syncVariantOptionValues(
        ProductVariant $variant,
        array $optionValueIds
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Empty
        |--------------------------------------------------------------------------
        */

        if (empty($optionValueIds)) {

            $variant->optionValues()->sync([]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize IDs
        |--------------------------------------------------------------------------
        */

        $optionValueIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $optionValueIds
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Load Option Values
        |--------------------------------------------------------------------------
        */

        $optionValues =
            ProductOptionValue::query()
                ->whereIn(
                    'id',
                    $optionValueIds
                )
                ->with([
                    'productOption:id,product_id,attribute_id,name',
                    'attributeValue:id,attribute_id,value',
                ])
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Validate IDs
        |--------------------------------------------------------------------------
        */

        if (
            $optionValues->count()
            !== count($optionValueIds)
        ) {

            throw new InvalidArgumentException(
                'One or more option values are invalid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Product Ownership
        |--------------------------------------------------------------------------
        */

        foreach ($optionValues as $optionValue) {

            if (
                !$optionValue->productOption
                ||
                (int) $optionValue
                    ->productOption
                    ->product_id
                !==
                (int) $variant->product_id
            ) {

                throw new InvalidArgumentException(
                    'One or more option values do not belong to this product.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Attribute Value Relationship
            |--------------------------------------------------------------------------
            */

            if (
                !$optionValue->attributeValue
                ||
                (int) $optionValue
                    ->attributeValue
                    ->attribute_id
                !==
                (int) $optionValue
                    ->productOption
                    ->attribute_id
            ) {

                throw new InvalidArgumentException(
                    'One or more option values do not match their product option attribute.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | One Value Per Product Option
        |--------------------------------------------------------------------------
        */

        $optionIds = $optionValues
            ->pluck(
                'productOption.id'
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->toArray();

        if (
            count($optionIds)
            !==
            count(
                array_unique($optionIds)
            )
        ) {

            throw new InvalidArgumentException(
                'A variant cannot contain multiple values from the same option.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Save Pivot
        |--------------------------------------------------------------------------
        */

        $variant->optionValues()->sync(
            $optionValueIds
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Product Images
    |--------------------------------------------------------------------------
    */

    protected function syncImages(
        Product $product,
        array $images
    ): void {

        foreach ($images as $imageData) {

            /*
            |--------------------------------------------------------------------------
            | Raw UploadedFile
            |--------------------------------------------------------------------------
            */

            if ($imageData instanceof UploadedFile) {

                $imageData = [
                    'file' => $imageData,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Uploaded File
            |--------------------------------------------------------------------------
            */

            if (
                isset($imageData['file'])
                &&
                $imageData['file']
                    instanceof UploadedFile
            ) {

                $file =
                    $imageData['file'];

                $filename =
                    Str::slug(
                        pathinfo(
                            $file->getClientOriginalName(),
                            PATHINFO_FILENAME
                        )
                    )
                    . '-'
                    . Str::random(20)
                    . '.'
                    . $file->getClientOriginalExtension();

                $directory =
                    public_path(
                        'products'
                    );

                if (!is_dir($directory)) {

                    mkdir(
                        $directory,
                        0755,
                        true
                    );
                }

                $file->move(
                    $directory,
                    $filename
                );

                $path =
                    'products/'
                    . $filename;

            } else {

                /*
                |--------------------------------------------------------------------------
                | Existing Image
                |--------------------------------------------------------------------------
                */

                $path =
                    $imageData['image']
                    ?? $imageData['path']
                    ?? null;
            }

            if (!$path) {
                continue;
            }

            ProductImage::create([
                'product_id' =>
                    $product->id,

                'image' =>
                    $path,

                'alt_text' =>
                    $imageData['alt_text']
                    ?? $product->name,

                'sort_order' =>
                    $imageData['sort_order']
                    ?? 0,

                'is_primary' =>
                    $imageData['is_primary']
                    ?? false,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Find Product By UUID
    |--------------------------------------------------------------------------
    */

    public function findByUuid(
        string $uuid
    ): ?Product {

        return Product::query()
            ->where('uuid', $uuid)
            ->with([
                'category',

                'images',
                'primaryImage',

                'attributeValues.attribute',

                'options.attribute',
                'options.values.attributeValue',

                'variants.optionValues.productOption.attribute',
                'variants.optionValues.attributeValue',
            ])
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Public Products
    |--------------------------------------------------------------------------
    */

    public function getPublicProducts(
        int $perPage = 15
    ) {

        return Product::query()
            ->where(
                'is_active',
                true
            )
            ->with([
                'category',

                'images',
                'primaryImage',

                'attributeValues.attribute',

                'options.attribute',
                'options.values.attributeValue',

                'variants.optionValues.productOption.attribute',
                'variants.optionValues.attributeValue',
            ])
            ->latest('id')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Featured Products
    |--------------------------------------------------------------------------
    */

    public function getFeaturedProducts(
        int $perPage = 15
    ) {

        return Product::query()
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_featured',
                true
            )
            ->with([
                'category',

                'images',
                'primaryImage',

                'attributeValues.attribute',

                'options.attribute',
                'options.values.attributeValue',

                'variants.optionValues.productOption.attribute',
                'variants.optionValues.attributeValue',
            ])
            ->latest('id')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Products
    |--------------------------------------------------------------------------
    */

    public function getAdminProducts(
        int $perPage = 15
    ) {

        return Product::query()
            ->with([
                'category',

                'images',
                'primaryImage',

                'attributeValues.attribute',

                'options.attribute',
                'options.values.attributeValue',

                'variants.optionValues.productOption.attribute',
                'variants.optionValues.attributeValue',
            ])
            ->latest('id')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Product By Slug
    |--------------------------------------------------------------------------
    */

    public function findBySlug(
        string $slug
    ): ?Product {

        return Product::query()
            ->where(
                'slug',
                $slug
            )
            ->where(
                'is_active',
                true
            )
            ->with([
                'category',

                'images',
                'primaryImage',

                'attributeValues.attribute',

                'options.attribute',
                'options.values.attributeValue',

                'variants.optionValues.productOption.attribute',
                'variants.optionValues.attributeValue',
            ])
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Product
    |--------------------------------------------------------------------------
    */

    public function delete(Product $product): void
    {
        $descriptionImages = DescriptionImageCleaner::extract(
            $product->description
        );

        DB::transaction(function () use ($product, $descriptionImages) {

            $product->delete();

            DB::afterCommit(function () use ($descriptionImages) {
                DescriptionImageCleaner::delete($descriptionImages);
            });
        });
    }
}