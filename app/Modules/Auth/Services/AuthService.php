<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        $user = $this->authRepository->createUser([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Assign default customer role
        |--------------------------------------------------------------------------
        */

        $customerRole = \App\Modules\Role\Models\Role::where(
            'name',
            'customer'
        )->first();

        if ($customerRole) {
            $user->roles()->syncWithoutDetaching([
                $customerRole->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->load([
            'roles.permissions',
        ]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate a user.
     */
    public function login(
        string $email,
        string $password
    ): array {
        $user = $this->authRepository->findByEmail($email);

        if (
            !$user ||
            !Hash::check($password, $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->load([
            'roles.permissions',
        ]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }


    /** * Update authenticated user's profile. */ 
    public function updateProfile(User $user, array $data): User 
    { 
        $user = $this->authRepository->updateUser( 
            $user, $data 
        ); 
        
        $user->load([ 
              'roles.permissions', 
              ]); 
        return $user; 
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(User $user): void
    {
        /*
        |--------------------------------------------------------------------------
        | Revoke current Sanctum token
        |--------------------------------------------------------------------------
        */

        $user->currentAccessToken()?->delete();
    }

    /**
     * Get authenticated user.
     */
    public function me(User $user): User
    {
        $user->load([
            'roles.permissions',
        ]);

        return $user;
    }
}

