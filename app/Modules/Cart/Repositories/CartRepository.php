<?php

namespace App\Modules\Cart\Repositories;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class CartRepository implements CartRepositoryInterface
{
    /**
     * Find the active cart for a user or guest session.
     */
    public function findActiveCart(
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): ?Cart {
        $query = Cart::query()
            ->active();

        if ($user) {
            return $query
                ->where(
                    'user_id',
                    $user->getAuthIdentifier()
                )
                ->first();
        }

        if ($sessionId) {
            return $query
                ->where(
                    'session_id',
                    $sessionId
                )
                ->first();
        }

        return null;
    }

    /**
     * Create a new cart.
     */
    public function createCart(
        ?Authenticatable $user = null,
        ?string $sessionId = null,
        string $currency = 'NPR'
    ): Cart {
        return Cart::create([
            'user_id' => $user?->getAuthIdentifier(),
            'session_id' => $user ? null : $sessionId,
            'status' => 'active',
            'currency' => $currency,
        ]);
    }

    /**
     * Find a cart item by UUID within a specific cart.
     */
    public function findCartItem(
        Cart $cart,
        string $itemUuid
    ): ?CartItem {
        return $cart->items()
            ->where('uuid', $itemUuid)
            ->first();
    }

    /**
     * Find a cart item using product variant ID.
     */
    public function findCartItemByVariant(
        Cart $cart,
        int $variantId
    ): ?CartItem {
        return $cart->items()
            ->where(
                'product_variant_id',
                $variantId
            )
            ->first();
    }

    /**
     * Create a cart item.
     */
    public function createCartItem(
        Cart $cart,
        array $data
    ): CartItem {
        return $cart->items()->create($data);
    }

    /**
     * Update a cart item.
     */
    public function updateCartItem(
        CartItem $item,
        array $data
    ): CartItem {
        $item->update($data);

        return $item->fresh();
    }

    /**
     * Delete a cart item.
     */
    public function deleteCartItem(
        CartItem $item
    ): bool {
        return (bool) $item->delete();
    }

    /**
     * Remove all items from a cart.
     */
    public function clearCart(
        Cart $cart
    ): int {
        return $cart->items()->delete();
    }

    /**
     * Load cart relationships required by the API.
     */
    public function loadCart(
        Cart $cart
    ): Cart {
        return $cart->load([
            'items.product.primaryImage',
            'items.productVariant.optionValues.productOption',
        ]);
    }
}

