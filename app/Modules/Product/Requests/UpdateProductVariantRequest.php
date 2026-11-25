<?php

namespace App\Modules\Product\Requests;

use App\Modules\Product\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Get variant UUID from route
        |--------------------------------------------------------------------------
        |
        | Route:
        |
        | /admin/products/{uuid}/variants/{variant}
        |
        | {variant} contains the variant UUID, NOT the database ID.
        |
        */

        $variantUuid = $this->route('variant');

        /*
        |--------------------------------------------------------------------------
        | Find current variant ID
        |--------------------------------------------------------------------------
        |
        | Laravel's unique rule needs the database ID when using ignore().
        |
        */

        $variantId = ProductVariant::query()
            ->where('uuid', $variantUuid)
            ->value('id');

        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | SKU
            |--------------------------------------------------------------------------
            */

            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'product_variants',
                    'sku'
                )->ignore($variantId),
            ],

            /*
            |--------------------------------------------------------------------------
            | Barcode
            |--------------------------------------------------------------------------
            */

            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',

                Rule::unique(
                    'product_variants',
                    'barcode'
                )->ignore($variantId),
            ],

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'price' => [
                'sometimes',
                'nullable',
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

            /*
            |--------------------------------------------------------------------------
            | Physical Information
            |--------------------------------------------------------------------------
            */

            'weight' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'image' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

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

