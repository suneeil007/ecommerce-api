<?php

namespace App\Modules\Cart\Repositories\Contracts;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use Illuminate\Contracts\Auth\Authenticatable;

interface CartRepositoryInterface
{
    public function findActiveCart(
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): ?Cart;

    public function createCart(
        ?Authenticatable $user = null,
        ?string $sessionId = null,
        string $currency = 'NPR'
    ): Cart;

    public function findCartItem(
        Cart $cart,
        string $itemUuid
    ): ?CartItem;

    public function findCartItemByVariant(
        Cart $cart,
        int $variantId
    ): ?CartItem;

    public function createCartItem(
        Cart $cart,
        array $data
    ): CartItem;

    public function updateCartItem(
        CartItem $item,
        array $data
    ): CartItem;

    public function deleteCartItem(
        CartItem $item
    ): bool;

    public function clearCart(
        Cart $cart
    ): int;

    public function loadCart(
        Cart $cart
    ): Cart;
}

