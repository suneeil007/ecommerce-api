<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\User;
use App\Modules\Role\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Customer Register
    |--------------------------------------------------------------------------
    */

    /**
     * Register a new customer.
     *
     * POST /api/auth/customer-register
     *
     * Public route.
     *
     * Every account created through this endpoint
     * receives the customer role.
     */
    public function customerRegister(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Registration
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Find Customer Role
        |--------------------------------------------------------------------------
        |
        | Customer registration must always have the customer
        | role available.
        |
        */

        $customerRole = Role::where(
            'name',
            'customer'
        )->first();


        if (!$customerRole) {
            return ApiResponse::error(
                'Customer registration is currently unavailable. Please contact support.',
                500
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Create Customer
        |--------------------------------------------------------------------------
        */

        $user = User::create([
            'name' => $validated['name'],

            'email' => $validated['email'],

            'password' => $validated['password'],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Assign Customer Role
        |--------------------------------------------------------------------------
        |
        | This endpoint can NEVER create an administrator account.
        |
        */

        $user->roles()->sync([
            $customerRole->id,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Load Roles + Permissions
        |--------------------------------------------------------------------------
        */

        $user->load([
            'roles.permissions',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create Customer Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            'customer-auth-token'
        )->plainTextToken;


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return ApiResponse::success(
            [
                'user' => $this->userData($user),

                'token' => $token,
            ],
            'Customer registration successful.',
            201
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Customer Login
    |--------------------------------------------------------------------------
    */

    /**
     * Login customer.
     *
     * POST /api/auth/customer-login
     *
     * Public route.
     *
     * Only users with the "customer" role are allowed.
     */
    public function customerLogin(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Credentials
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = User::with([
            'roles.permissions',
        ])
            ->where(
                'email',
                $validated['email']
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Verify Credentials
        |--------------------------------------------------------------------------
        */

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Verify Customer Role
        |--------------------------------------------------------------------------
        */

        $isCustomer = $user->roles->contains(
            function ($role) {
                return strtolower(
                    trim($role->name)
                ) === 'customer';
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Reject Non-Customer Account
        |--------------------------------------------------------------------------
        */

        if (!$isCustomer) {
            return ApiResponse::error(
                'This account is not a customer account. Please use the administrator login.',
                403
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Verify Customer Account Status
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {
            return ApiResponse::error(
                'Your account has been deactivated. Please contact support.',
                403
            );
        }



        /*
        |--------------------------------------------------------------------------
        | Revoke Previous Tokens
        |--------------------------------------------------------------------------
        */

        $user->tokens()->delete();


        /*
        |--------------------------------------------------------------------------
        | Create Customer Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            'customer-auth-token'
        )->plainTextToken;


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return ApiResponse::success(
            [
                'user' => $this->userData($user),

                'token' => $token,
            ],
            'Customer login successful.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Admin / Staff Login
    |--------------------------------------------------------------------------
    */

    /**
     * Login administrator or staff user.
     *
     * POST /api/auth/login
     *
     * Public route.
     */
    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Credentials
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = User::with([
            'roles.permissions',
        ])
            ->where(
                'email',
                $validated['email']
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Verify Credentials
        |--------------------------------------------------------------------------
        */

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Verify Admin / Staff Access
        |--------------------------------------------------------------------------
        |
        | Prevent customer accounts from using the administrator
        | login endpoint.
        |
        */

        $isCustomer = $user->roles->contains(
            function ($role) {
                return strtolower(
                    trim($role->name)
                ) === 'customer';
            }
        );


        if ($isCustomer) {
            return ApiResponse::error(
                'This account is a customer account. Please use the customer login.',
                403
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Revoke Previous Tokens
        |--------------------------------------------------------------------------
        */

        $user->tokens()->delete();


        /*
        |--------------------------------------------------------------------------
        | Create Admin Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            'auth-token'
        )->plainTextToken;


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return ApiResponse::success(
            [
                'user' => $this->userData($user),

                'token' => $token,
            ],
            'Login successful.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    /**
     * Logout current authenticated user.
     *
     * POST /api/auth/logout
     *
     * Middleware:
     * auth:sanctum
     */
    public function logout(Request $request)
    {
        /*
         * Delete only the token currently being used.
         */
        $request->user()
            ->currentAccessToken()
            ?->delete();


        return ApiResponse::success(
            null,
            'Logout successful.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Current User
    |--------------------------------------------------------------------------
    */

    /**
     * Get authenticated user.
     *
     * GET /api/auth/me
     *
     * Middleware:
     * auth:sanctum
     */
    public function me(Request $request)
    {
        $user = $request->user()->load([
            'roles.permissions',
        ]);


        return ApiResponse::success(
            $this->userData($user),
            'Authenticated user.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | User Data
    |--------------------------------------------------------------------------
    */

    /**
     * Format authenticated user response.
     */
    private function userData(User $user): array
    {
        return [
            
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => (bool) $user->is_active,

            /*
             * User roles.
             */
            'roles' => $user->roles
                ->pluck('name')
                ->unique()
                ->values()
                ->toArray(),

            /*
             * All permissions from all roles.
             */
            'permissions' => $user->roles
                ->flatMap(function ($role) {
                    return $role->permissions
                        ->pluck('name');
                })
                ->unique()
                ->values()
                ->toArray(),
        ];
    }
}