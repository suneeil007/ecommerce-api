<?php

namespace App\Modules\Order\Repositories\Contracts;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function findByUuid(string $uuid): ?Order;

    public function findForUser(
        Authenticatable $user,
        string $uuid
    ): ?Order;

    public function getForUser(
        Authenticatable $user
    ): Collection;

    public function getAll(): Collection;

    public function create(array $data): Order;

    public function createItem(
        Order $order,
        array $data
    ): mixed;

    public function loadOrder(
        Order $order
    ): Order;
}

