<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductOptionValue;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantService
{
    /**
     * Get all variants belonging to a product.
     */
    public function getByProduct(Product $product)
    {
        return $product->variants()
            ->with([
                'optionValues.productOption',
            ])
            ->get();
    }

    /**
     * Find one variant belonging to a product.
     */
    public function findByUuid(
        Product $product,
        string $variantUuid
    ): ?ProductVariant {
        return ProductVariant::query()
            ->where('uuid', $variantUuid)
            ->where('product_id', $product->id)
            ->with([
                'product',
                'optionValues.productOption',
            ])
            ->first();
    }

    /**
     * Create a new product variant.
     */
    public function create(
        Product $product,
        array $data
    ): ProductVariant {
        return DB::transaction(function () use (
            $product,
            $data
        ) {
            $optionValueIds = $data['option_value_ids'] ?? [];

            unset($data['option_value_ids']);

            /*
            |--------------------------------------------------------------------------
            | Validate option combination
            |--------------------------------------------------------------------------
            */

            $this->validateOptionCombination(
                $product,
                $optionValueIds
            );

            /*
            |--------------------------------------------------------------------------
            | Create variant
            |--------------------------------------------------------------------------
            */

            $variant = $product->variants()->create($data);

            /*
            |--------------------------------------------------------------------------
            | Sync option values
            |--------------------------------------------------------------------------
            */

            $this->syncOptionValues(
                $variant,
                $optionValueIds
            );

            /*
            |--------------------------------------------------------------------------
            | Load relationships
            |--------------------------------------------------------------------------
            */

            $variant->load([
                'product',
                'optionValues.productOption',
            ]);

            return $variant;
        });
    }

    /**
     * Update one product variant.
     *
     * This method updates ONLY the selected variant.
     */
    public function update(
        ProductVariant $variant,
        array $data
    ): ProductVariant {
        $variant->update($data);

        $variant->load([
            'product',
            'optionValues.productOption',
        ]);

        return $variant;
    }

    /**
     * Delete one product variant.
     */
    public function delete(
        ProductVariant $variant
    ): void {
        DB::transaction(function () use ($variant) {
            /*
            |--------------------------------------------------------------------------
            | Delete variant
            |--------------------------------------------------------------------------
            |
            | product_variant_option_values rows are automatically
            | deleted because the foreign key uses ON DELETE CASCADE.
            |
            */

            $variant->delete();
        });
    }

    /**
     * Update option values assigned to a variant.
     */
    public function updateOptions(
        ProductVariant $variant,
        array $optionValueIds
    ): ProductVariant {
        return DB::transaction(function () use (
            $variant,
            $optionValueIds
        ) {
            /*
            |--------------------------------------------------------------------------
            | Validate new combination
            |--------------------------------------------------------------------------
            */

            $this->validateOptionCombination(
                $variant->product,
                $optionValueIds,
                $variant->id
            );

            /*
            |--------------------------------------------------------------------------
            | Sync option values
            |--------------------------------------------------------------------------
            */

            $this->syncOptionValues(
                $variant,
                $optionValueIds
            );

            /*
            |--------------------------------------------------------------------------
            | Reload relationships
            |--------------------------------------------------------------------------
            */

            $variant->load([
                'product',
                'optionValues.productOption',
            ]);

            return $variant;
        });
    }

    /**
     * Sync option values for a variant.
     */
    protected function syncOptionValues(
        ProductVariant $variant,
        array $optionValueIds
    ): void {
        /*
        |--------------------------------------------------------------------------
        | No options
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
        | Validate option values
        |--------------------------------------------------------------------------
        */

        $optionValues = ProductOptionValue::query()
            ->whereIn('id', $optionValueIds)
            ->with([
                'productOption:id,uuid,product_id,name,slug,display_name',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Check that every option value exists
        |--------------------------------------------------------------------------
        */

        if (
            $optionValues->count() !==
            count($optionValueIds)
        ) {
            throw ValidationException::withMessages([
                'option_value_ids' => [
                    'One or more option values are invalid.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check product ownership
        |--------------------------------------------------------------------------
        */

        foreach ($optionValues as $optionValue) {
            if (
                !$optionValue->productOption ||
                (int) $optionValue->productOption->product_id !==
                    (int) $variant->product_id
            ) {
                throw ValidationException::withMessages([
                    'option_value_ids' => [
                        'One or more option values do not belong to this product.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Check duplicate parent options
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | Color: Black
        | Color: Red
        |
        | This is invalid.
        |
        */

        $optionIds = $optionValues
            ->pluck('productOption.id')
            ->map(
                fn ($id) => (int) $id
            )
            ->toArray();

        if (
            count($optionIds) !==
            count(array_unique($optionIds))
        ) {
            throw ValidationException::withMessages([
                'option_value_ids' => [
                    'A variant cannot contain multiple values from the same option.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Sync pivot table
        |--------------------------------------------------------------------------
        */

        $variant->optionValues()->sync(
            $optionValueIds
        );
    }

    /**
     * Validate that a variant combination is unique.
     *
     * Example:
     *
     * Black + 256 GB
     *
     * cannot exist on two different variants of the same product.
     */
    protected function validateOptionCombination(
        Product $product,
        array $optionValueIds,
        ?int $ignoreVariantId = null
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Empty combination
        |--------------------------------------------------------------------------
        |
        | A product can have only ONE variant without option values.
        |
        */

        if (empty($optionValueIds)) {
            $query = ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereDoesntHave('optionValues');

            if ($ignoreVariantId !== null) {
                $query->where('id', '!=', $ignoreVariantId);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'option_value_ids' => [
                        'A variant without option values already exists for this product.',
                    ],
                ]);
            }

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
        | Make canonical combination
        |--------------------------------------------------------------------------
        */

        sort($optionValueIds);

        /*
        |--------------------------------------------------------------------------
        | Find variants for this product
        |--------------------------------------------------------------------------
        */

        $variants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->with('optionValues:id')
            ->when(
                $ignoreVariantId !== null,
                fn ($query) =>
                    $query->where(
                        'id',
                        '!=',
                        $ignoreVariantId
                    )
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Compare combinations
        |--------------------------------------------------------------------------
        */

        foreach ($variants as $existingVariant) {
            $existingIds = $existingVariant
                ->optionValues
                ->pluck('id')
                ->map(
                    fn ($id) => (int) $id
                )
                ->sort()
                ->values()
                ->toArray();

            if ($existingIds === $optionValueIds) {
                throw ValidationException::withMessages([
                    'option_value_ids' => [
                        'This option combination already exists for this product.',
                    ],
                ]);
            }
        }
    }
}

