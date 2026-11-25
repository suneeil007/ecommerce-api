<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function getCustomers(array $filters = []): LengthAwarePaginator
    {
        return $this->userRepository->paginateCustomers($filters);
    }

    public function getCustomer(string $uuid): User
    {
        return $this->userRepository->findCustomerByUuid($uuid);
    }

    public function updateCustomer(string $uuid, array $data): User
    {
        return $this->userRepository->updateCustomer($uuid, $data);
    }

    public function updateCustomerStatus(
        string $uuid,
        bool $isActive
    ): User {
        return $this->userRepository->updateCustomerStatus(
            $uuid,
            $isActive
        );
    }

    public function deleteCustomer(string $uuid): bool
    {
        return $this->userRepository->deleteCustomer($uuid);
    }

    public function getCustomerOrders(
        string $uuid,
        array $filters = []
    ): LengthAwarePaginator {
        return $this->userRepository->paginateCustomerOrders(
            $uuid,
            $filters
        );
    }
}