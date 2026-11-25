<?php

namespace App\Modules\Profile\Services;

use App\Models\User;
use App\Modules\Profile\Models\CustomerProfile;
use App\Modules\Profile\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function __construct(
        protected ProfileRepositoryInterface $profileRepository
    ) {
    }

    /**
     * Get the authenticated customer's profile.
     *
     * Creates an empty profile when one does not exist yet.
     */
    public function getProfile(
        User $user
    ): CustomerProfile {
        $profile = $this->profileRepository->findByUserId(
            $user->id
        );

        if (!$profile) {
            $profile = $this->profileRepository->create(
                $user,
                []
            );
        }

        return $profile->load('user');
    }

    /**
     * Update the authenticated customer's profile.
     */
    public function updateProfile(
        User $user,
        array $data
    ): CustomerProfile {
        return DB::transaction(function () use (
            $user,
            $data
        ) {
            $profile = $this->profileRepository->findByUserId(
                $user->id
            );

            if (!$profile) {
                $profile = $this->profileRepository->create(
                    $user,
                    $data
                );
            } else {
                $profile = $this->profileRepository->update(
                    $profile,
                    $data
                );
            }

            return $profile->load('user');
        });
    }

    /**
     * Delete the customer's profile.
     */
    public function deleteProfile(
        User $user
    ): bool {
        $profile = $this->profileRepository->findByUserId(
            $user->id
        );

        if (!$profile) {
            return false;
        }

        return $this->profileRepository->delete(
            $profile
        );
    }
}
