<?php

namespace App\Modules\Auth\Repositories\Contracts;

use App\Models\User;

interface AuthRepositoryInterface
{
    /**
     * Create a new user.
     */
    public function createUser(array $data): User;

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Update a user.
     */
    public function updateUser(User $user, array $data): User;
}

