<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return User::create($data);
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Update a user.
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }
}

