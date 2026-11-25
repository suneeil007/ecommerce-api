<?php

namespace App\Modules\Order\Repositories;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Find By UUID
    |--------------------------------------------------------------------------
    */

    public function findByUuid(
        string $uuid
    ): ?Order {
        return Order::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Find Order For User
    |--------------------------------------------------------------------------
    */

    public function findForUser(
        Authenticatable $user,
        string $uuid
    ): ?Order {
        return Order::query()
            ->where('uuid', $uuid)
            ->where(
                'user_id',
                $user->getAuthIdentifier()
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Orders
    |--------------------------------------------------------------------------
    */

    public function getForUser(
        Authenticatable $user
    ): Collection {
        return Order::query()
            ->where(
                'user_id',
                $user->getAuthIdentifier()
            )
            ->with([
                'items.product',
                'items.product.images',
                'items.productVariant',
                'items.productVariant.optionValues.productOption',
            ])
            ->latest('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Orders
    |--------------------------------------------------------------------------
    */

    public function getAll(): Collection
    {
        return Order::query()
            ->with([
                'items.product',
                'items.product.images',
                'items.productVariant',
                'items.productVariant.optionValues.productOption',
                'user',
            ])
            ->latest('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): Order {
        return Order::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order Item
    |--------------------------------------------------------------------------
    */

    public function createItem(
        Order $order,
        array $data
    ): mixed {
        return $order->items()->create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Load Complete Order
    |--------------------------------------------------------------------------
    */

    public function loadOrder(
        Order $order
    ): Order {
        return $order->load([
            'user',
            'cart',

            'items',

            /*
            | Product
            */

            'items.product',
            'items.product.images',

            /*
            | Product Variant
            */

            'items.productVariant',

            /*
            | Variant Options
            */

            'items.productVariant.optionValues.productOption',
        ]);
    }
}