<?php

namespace App\Modules\Checkout\Services;

use App\Models\User;
use App\Modules\Checkout\Enums\CheckoutStatus;
use App\Modules\Checkout\Models\Checkout;
use App\Modules\Checkout\Repositories\Contracts\CheckoutRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        protected CheckoutRepositoryInterface $checkoutRepository
    ) {
    }

    /**
     * Create a checkout from the user's active cart.
     *
     * Important:
     * - Does NOT deduct stock.
     * - Does NOT convert the cart.
     * - Does NOT create an order.
     *
     * Those operations happen later in the order/payment flow.
     */
    public function createFromCart(
        User $user,
        ?string $notes = null
    ): Checkout {
        return DB::transaction(function () use ($user, $notes) {

            $cart = $this->checkoutRepository->getActiveCart($user);

            if (!$cart) {
                throw new RuntimeException(
                    'Your cart is empty.'
                );
            }

            if ($cart->items->isEmpty()) {
                throw new RuntimeException(
                    'Your cart is empty.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Cart Items
            |--------------------------------------------------------------------------
            */

            foreach ($cart->items as $cartItem) {

                $product = $cartItem->product;

                if (!$product) {
                    throw new RuntimeException(
                        'A product in your cart no longer exists.'
                    );
                }

                if (!$product->is_active) {
                    throw new RuntimeException(
                        "Product '{$product->name}' is no longer available."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Variant Validation
                |--------------------------------------------------------------------------
                */

                $variant = $cartItem->productVariant;

                if ($cartItem->product_variant_id && !$variant) {
                    throw new RuntimeException(
                        "The selected variant for '{$product->name}' no longer exists."
                    );
                }

                if ($variant && !$variant->is_active) {
                    throw new RuntimeException(
                        "The selected variant for '{$product->name}' is no longer available."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Stock Validation
                |--------------------------------------------------------------------------
                */

                if ($variant) {
                    if ($variant->stock < $cartItem->quantity) {
                        throw new RuntimeException(
                            "Insufficient stock for '{$product->name}'. "
                            . "Available stock: {$variant->stock}."
                        );
                    }
                } else {
                    if ($product->stock < $cartItem->quantity) {
                        throw new RuntimeException(
                            "Insufficient stock for '{$product->name}'. "
                            . "Available stock: {$product->stock}."
                        );
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Totals
            |--------------------------------------------------------------------------
            |
            | At the moment:
            | - Discount = 0
            | - Shipping = 0
            | - Tax = 0
            |
            | Coupon, shipping and tax logic can be added later.
            |
            */

            $subtotal = $cart->items->sum(
                fn ($item) => (float) $item->total_price
            );

            $discountAmount = 0;
            $shippingAmount = 0;
            $taxAmount = 0;

            $totalAmount =
                $subtotal
                - $discountAmount
                + $shippingAmount
                + $taxAmount;

            /*
            |--------------------------------------------------------------------------
            | Create Checkout
            |--------------------------------------------------------------------------
            */

            $checkout = $this->checkoutRepository->create([
                'user_id' => $user->id,
                'cart_id' => $cart->id,
                'status' => CheckoutStatus::PENDING,
                'currency' => $cart->currency ?? 'NPR',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'coupon_code' => null,
                'notes' => $notes,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Checkout Item Snapshots
            |--------------------------------------------------------------------------
            */

            foreach ($cart->items as $cartItem) {

                $variant = $cartItem->productVariant;

                $checkout->items()->create([
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'product_name' => $cartItem->product->name,
                    'variant_name' => $variant
                        ? $this->buildVariantName($variant)
                        : null,
                    'sku' => $variant?->sku
                        ?? $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_price' => $cartItem->total_price,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Return Complete Checkout
            |--------------------------------------------------------------------------
            */

            return $this->checkoutRepository->loadCheckout($checkout);
        });
    }

    /**
     * Get a checkout belonging to the authenticated user.
     */
    public function getUserCheckout(
        User $user,
        string $uuid
    ): Checkout {
        $checkout = $this->checkoutRepository->getUserCheckout(
            $user,
            $uuid
        );

        if (!$checkout) {
            throw new RuntimeException(
                'Checkout not found.'
            );
        }

        return $checkout;
    }

    /**
     * Get all checkouts belonging to the authenticated user.
     */
    public function getUserCheckouts(User $user)
    {
        return $this->checkoutRepository->getUserCheckouts($user);
    }

    /**
     * Build a readable variant name.
     *
     * Example:
     * Color: Black, Storage: 256 GB
     */
    protected function buildVariantName($variant): ?string
    {
        if (!$variant->relationLoaded('optionValues')) {
            $variant->load(
                'optionValues.productOption'
            );
        }

        $parts = [];

        foreach ($variant->optionValues as $optionValue) {

            $optionName = $optionValue->productOption?->name;

            if (!$optionName) {
                continue;
            }

            $parts[] = "{$optionName}: {$optionValue->display_value}";
        }

        return !empty($parts)
            ? implode(', ', $parts)
            : ($variant->name ?: null);
    }
}

