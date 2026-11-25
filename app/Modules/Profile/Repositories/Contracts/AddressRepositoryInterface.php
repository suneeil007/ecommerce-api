<?php

namespace App\Modules\Profile\Repositories\Contracts;

use App\Models\User;
use App\Modules\Profile\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;

interface AddressRepositoryInterface
{
    /**
     * Get all addresses for a user.
     */
    public function getByUserId(
        int $userId
    ): Collection;

    /**
     * Find an address by UUID.
     */
    public function findByUuid(
        string $uuid
    ): ?CustomerAddress;

    /**
     * Find an address by UUID belonging to a specific user.
     */
    public function findByUserAndUuid(
        int $userId,
        string $uuid
    ): ?CustomerAddress;

    /**
     * Get the default address for a user.
     */
    public function getDefault(
        int $userId
    ): ?CustomerAddress;

    /**
     * Create an address for a user.
     */
    public function create(
        User $user,
        array $data
    ): CustomerAddress;

    /**
     * Update an address.
     */
    public function update(
        CustomerAddress $address,
        array $data
    ): CustomerAddress;

    /**
     * Delete an address.
     */
    public function delete(
        CustomerAddress $address
    ): bool;

    /**
     * Remove the default status from all addresses
     * belonging to a user.
     */
    public function clearDefault(
        int $userId
    ): int;

    /**
     * Set an address as the default address.
     */
    public function setDefault(
        CustomerAddress $address
    ): CustomerAddress;
}
