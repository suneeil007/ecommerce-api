<?php

namespace App\Modules\Profile\Repositories;

use App\Models\User;
use App\Modules\Profile\Models\CustomerProfile;
use App\Modules\Profile\Repositories\Contracts\ProfileRepositoryInterface;

class ProfileRepository implements ProfileRepositoryInterface
{
    /**
     * Find a profile by user ID.
     */
    public function findByUserId(
        int $userId
    ): ?CustomerProfile {
        return CustomerProfile::query()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Find a profile by UUID.
     */
    public function findByUuid(
        string $uuid
    ): ?CustomerProfile {
        return CustomerProfile::query()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Create a profile for a user.
     */
    public function create(
        User $user,
        array $data
    ): CustomerProfile {
        return $user->customerProfile()->create($data);
    }

    /**
     * Update a profile.
     */
    public function update(
        CustomerProfile $profile,
        array $data
    ): CustomerProfile {
        $profile->update($data);

        return $profile->refresh();
    }

    /**
     * Delete a profile.
     */
    public function delete(
        CustomerProfile $profile
    ): bool {
        return (bool) $profile->delete();
    }
}
