<?php

namespace App\Modules\Cart\Services;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Repositories\Contracts\CartRepositoryInterface;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CartService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepository
    ) {
    }

    /**
     * Get the active cart for an authenticated user
     * or a guest session.
     *
     * If the cart does not exist, it will be created.
     */
    public function getCart(
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): Cart {
        $this->validateCartOwner(
            $user,
            $sessionId
        );

        $cart = $this->cartRepository->findActiveCart(
            $user,
            $sessionId
        );

        if (!$cart) {
            $cart = $this->cartRepository->createCart(
                $user,
                $sessionId,
                'NPR'
            );
        }

        return $this->cartRepository->loadCart($cart);
    }

    /**
     * Add a product variant to the cart.
     *
     * If the variant already exists in the cart,
     * the quantity will be increased.
     */
    public function addItem(
        string $variantUuid,
        int $quantity,
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): Cart {
        return DB::transaction(function () use (
            $variantUuid,
            $quantity,
            $user,
            $sessionId
        ) {
            $this->validateCartOwner(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Validate quantity
            |--------------------------------------------------------------------------
            */

            if ($quantity < 1) {
                throw new RuntimeException(
                    'Quantity must be at least 1.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Find Variant
            |--------------------------------------------------------------------------
            */

            $variant = ProductVariant::query()
                ->with('product')
                ->where('uuid', $variantUuid)
                ->where('is_active', true)
                ->first();

            if (!$variant) {
                throw new RuntimeException(
                    'The selected product variant does not exist or is inactive.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Product
            |--------------------------------------------------------------------------
            */

            if (
                !$variant->product ||
                !$variant->product->is_active
            ) {
                throw new RuntimeException(
                    'The product associated with this variant is inactive.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Get/Create Cart
            |--------------------------------------------------------------------------
            */

            $cart = $this->getCart(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Find Existing Cart Item
            |--------------------------------------------------------------------------
            */

            $item = $this->cartRepository
                ->findCartItemByVariant(
                    $cart,
                    $variant->id
                );

            /*
            |--------------------------------------------------------------------------
            | Calculate New Quantity
            |--------------------------------------------------------------------------
            */

            $existingQuantity = $item
                ? (int) $item->quantity
                : 0;

            $newQuantity = $existingQuantity + $quantity;

            /*
            |--------------------------------------------------------------------------
            | Validate Stock
            |--------------------------------------------------------------------------
            */

            $this->validateStock(
                $variant,
                $newQuantity
            );

            /*
            |--------------------------------------------------------------------------
            | Get Current Price
            |--------------------------------------------------------------------------
            */

            $unitPrice = $this->getVariantPrice(
                $variant
            );

            $totalPrice = $unitPrice * $newQuantity;

            /*
            |--------------------------------------------------------------------------
            | Update Existing Item
            |--------------------------------------------------------------------------
            */

            if ($item) {
                $item = $this->cartRepository->updateCartItem(
                    $item,
                    [
                        'quantity' => $newQuantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create New Item
            |--------------------------------------------------------------------------
            */

            else {
                $item = $this->cartRepository->createCartItem(
                    $cart,
                    [
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * $quantity,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Return Updated Cart
            |--------------------------------------------------------------------------
            */

            return $this->cartRepository->loadCart(
                $cart->fresh()
            );
        });
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItem(
        string $itemUuid,
        int $quantity,
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): Cart {
        return DB::transaction(function () use (
            $itemUuid,
            $quantity,
            $user,
            $sessionId
        ) {
            $this->validateCartOwner(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Validate Quantity
            |--------------------------------------------------------------------------
            */

            if ($quantity < 1) {
                throw new RuntimeException(
                    'Quantity must be at least 1.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Get Existing Cart
            |--------------------------------------------------------------------------
            */

            $cart = $this->getExistingCart(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Find Cart Item
            |--------------------------------------------------------------------------
            */

            $item = $this->cartRepository->findCartItem(
                $cart,
                $itemUuid
            );

            if (!$item) {
                throw new RuntimeException(
                    'Cart item not found.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Load Variant
            |--------------------------------------------------------------------------
            */

            $item->load([
                'product',
                'productVariant',
            ]);

            $variant = $item->productVariant;

            /*
            |--------------------------------------------------------------------------
            | Validate Variant
            |--------------------------------------------------------------------------
            */

            if (!$variant || !$variant->is_active) {
                throw new RuntimeException(
                    'The selected product variant is unavailable.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Product
            |--------------------------------------------------------------------------
            */

            if (
                !$variant->product ||
                !$variant->product->is_active
            ) {
                throw new RuntimeException(
                    'The product associated with this variant is inactive.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Stock
            |--------------------------------------------------------------------------
            */

            $this->validateStock(
                $variant,
                $quantity
            );

            /*
            |--------------------------------------------------------------------------
            | Get Current Price
            |--------------------------------------------------------------------------
            */

            $unitPrice = $this->getVariantPrice(
                $variant
            );

            /*
            |--------------------------------------------------------------------------
            | Update Item
            |--------------------------------------------------------------------------
            */

            $this->cartRepository->updateCartItem(
                $item,
                [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Return Updated Cart
            |--------------------------------------------------------------------------
            */

            return $this->cartRepository->loadCart(
                $cart->fresh()
            );
        });
    }

    /**
     * Remove a single cart item.
     */
    public function removeItem(
        string $itemUuid,
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): Cart {
        return DB::transaction(function () use (
            $itemUuid,
            $user,
            $sessionId
        ) {
            $this->validateCartOwner(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Get Existing Cart
            |--------------------------------------------------------------------------
            */

            $cart = $this->getExistingCart(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Find Item
            |--------------------------------------------------------------------------
            */

            $item = $this->cartRepository->findCartItem(
                $cart,
                $itemUuid
            );

            if (!$item) {
                throw new RuntimeException(
                    'Cart item not found.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Delete Item
            |--------------------------------------------------------------------------
            */

            $this->cartRepository->deleteCartItem(
                $item
            );

            /*
            |--------------------------------------------------------------------------
            | Return Updated Cart
            |--------------------------------------------------------------------------
            */

            return $this->cartRepository->loadCart(
                $cart->fresh()
            );
        });
    }

    /**
     * Remove all items from the cart.
     */
    public function clearCart(
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): Cart {
        return DB::transaction(function () use (
            $user,
            $sessionId
        ) {
            $this->validateCartOwner(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Get Existing Cart
            |--------------------------------------------------------------------------
            */

            $cart = $this->getExistingCart(
                $user,
                $sessionId
            );

            /*
            |--------------------------------------------------------------------------
            | Delete All Items
            |--------------------------------------------------------------------------
            */

            $this->cartRepository->clearCart(
                $cart
            );

            /*
            |--------------------------------------------------------------------------
            | Return Empty Cart
            |--------------------------------------------------------------------------
            */

            return $this->cartRepository->loadCart(
                $cart->fresh()
            );
        });
    }

    /**
     * Get an existing active cart.
     *
     * Unlike getCart(), this method does not create
     * a new cart when one does not exist.
     */
    protected function getExistingCart(
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): Cart {
        $this->validateCartOwner(
            $user,
            $sessionId
        );

        $cart = $this->cartRepository->findActiveCart(
            $user,
            $sessionId
        );

        if (!$cart) {
            throw new RuntimeException(
                'Cart not found.'
            );
        }

        return $cart;
    }

    /**
     * Validate that the cart belongs to either
     * an authenticated user or a guest session.
     */
    protected function validateCartOwner(
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): void {
        if (!$user && !$sessionId) {
            throw new RuntimeException(
                'Unable to identify the cart. Please authenticate or provide a cart session ID.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Authenticated users should not use a guest session.
        |--------------------------------------------------------------------------
        */

        if ($user && $sessionId) {
            throw new RuntimeException(
                'Authenticated users must use their authenticated cart.'
            );
        }
    }

    /**
     * Validate requested quantity against available stock.
     */
    protected function validateStock(
        ProductVariant $variant,
        int $quantity
    ): void {
        $stock = (int) $variant->stock;

        if ($stock < $quantity) {
            throw new RuntimeException(
                "Only {$stock} item(s) are available for {$variant->sku}."
            );
        }
    }

    /**
     * Get the current selling price.
     *
     * Variant price has priority.
     * Product price is used as fallback.
     */
    protected function getVariantPrice(
        ProductVariant $variant
    ): float {
        if ($variant->price !== null) {
            return (float) $variant->price;
        }

        if (
            $variant->product &&
            $variant->product->price !== null
        ) {
            return (float) $variant->product->price;
        }

        throw new RuntimeException(
            'No valid price is configured for this product variant.'
        );
    }
}

