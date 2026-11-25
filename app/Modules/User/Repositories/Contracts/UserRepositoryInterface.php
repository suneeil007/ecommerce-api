<?php

namespace App\Modules\User\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function paginateCustomers(array $filters = []): LengthAwarePaginator;

    public function findCustomerByUuid(string $uuid): User;

    public function updateCustomer(string $uuid, array $data): User;

    public function updateCustomerStatus(string $uuid, bool $isActive): User;

    public function deleteCustomer(string $uuid): bool;

    public function paginateCustomerOrders(
        string $uuid,
        array $filters = []
    ): LengthAwarePaginator;
}