<?php

namespace App\Modules\Checkout\Repositories\Contracts;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Checkout\Models\Checkout;

interface CheckoutRepositoryInterface
{
    public function create(array $data): Checkout;

    public function findByUuid(string $uuid): ?Checkout;

    public function getUserCheckout(User $user, string $uuid): ?Checkout;

    public function getUserCheckouts(User $user);

    public function getActiveCart(User $user): ?Cart;

    public function loadCheckout(Checkout $checkout): Checkout;
}

