<?php

namespace App\Modules\Profile\Repositories;

use App\Models\User;
use App\Modules\Profile\Models\CustomerAddress;
use App\Modules\Profile\Repositories\Contracts\AddressRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AddressRepository implements AddressRepositoryInterface
{
    /**
     * Get all addresses for a user.
     */
    public function getByUserId(
        int $userId
    ): Collection {
        return CustomerAddress::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Find an address by UUID.
     */
    public function findByUuid(
        string $uuid
    ): ?CustomerAddress {
        return CustomerAddress::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Find an address by UUID belonging to a specific user.
     */
    public function findByUserAndUuid(
        int $userId,
        string $uuid
    ): ?CustomerAddress {
        return CustomerAddress::query()
            ->where('user_id', $userId)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Get the default address for a user.
     */
    public function getDefault(
        int $userId
    ): ?CustomerAddress {
        return CustomerAddress::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Create an address for a user.
     */
    public function create(
        User $user,
        array $data
    ): CustomerAddress {
        return $user->customerAddresses()->create($data);
    }

    /**
     * Update an address.
     */
    public function update(
        CustomerAddress $address,
        array $data
    ): CustomerAddress {
        $address->update($data);

        return $address->refresh();
    }

    /**
     * Delete an address.
     */
    public function delete(
        CustomerAddress $address
    ): bool {
        return (bool) $address->delete();
    }

    /**
     * Remove the default status from all addresses
     * belonging to a user.
     */
    public function clearDefault(
        int $userId
    ): int {
        return CustomerAddress::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->update([
                'is_default' => false,
            ]);
    }

    /**
     * Set an address as the default address.
     */
    public function setDefault(
        CustomerAddress $address
    ): CustomerAddress {
        $address->update([
            'is_default' => true,
        ]);

        return $address->refresh();
    }
}
