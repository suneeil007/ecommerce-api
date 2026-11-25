<?php

namespace App\Modules\Profile\Repositories\Contracts;

use App\Modules\Profile\Models\CustomerProfile;
use App\Models\User;

interface ProfileRepositoryInterface
{
    /**
     * Find a profile by user ID.
     */
    public function findByUserId(
        int $userId
    ): ?CustomerProfile;

    /**
     * Find a profile by UUID.
     */
    public function findByUuid(
        string $uuid
    ): ?CustomerProfile;

    /**
     * Create a profile for a user.
     */
    public function create(
        User $user,
        array $data
    ): CustomerProfile;

    /**
     * Update a profile.
     */
    public function update(
        CustomerProfile $profile,
        array $data
    ): CustomerProfile;

    /**
     * Delete a profile.
     */
    public function delete(
        CustomerProfile $profile
    ): bool;
}
