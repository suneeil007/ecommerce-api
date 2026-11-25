<?php

namespace App\Modules\Checkout\Repositories;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Checkout\Models\Checkout;
use App\Modules\Checkout\Repositories\Contracts\CheckoutRepositoryInterface;

class CheckoutRepository implements CheckoutRepositoryInterface
{
    public function create(array $data): Checkout
    {
        return Checkout::create($data);
    }

    public function findByUuid(string $uuid): ?Checkout
    {
        return Checkout::findByUuid($uuid);
    }

    public function getUserCheckout(
        User $user,
        string $uuid
    ): ?Checkout {
        return Checkout::query()
            ->where('user_id', $user->id)
            ->where('uuid', $uuid)
            ->with([
                'items.product',
                'items.productVariant',
            ])
            ->first();
    }

    public function getUserCheckouts(User $user)
    {
        return Checkout::query()
            ->where('user_id', $user->id)
            ->withCount('items')
            ->latest('id')
            ->get();
    }

    public function getActiveCart(User $user): ?Cart
    {
        return Cart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with([
                'items.product',
                'items.productVariant',
                'items.productVariant.optionValues.productOption',
            ])
            ->latest('id')
            ->first();
    }

    public function loadCheckout(Checkout $checkout): Checkout
    {
        return $checkout->load([
            'user',
            'cart',
            'items.product',
            'items.productVariant',
            'items.productVariant.optionValues.productOption',
        ]);
    }
}

