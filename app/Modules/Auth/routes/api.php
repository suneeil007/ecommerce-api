<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
|
| Customer:
|   POST /api/auth/customer-register
|   POST /api/auth/customer-login
|
| Admin / Staff:
|   POST /api/auth/login
|
| Authenticated:
|   GET  /api/auth/me
|   PUT  /api/auth/profile
|   POST /api/auth/logout
|
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Customer Registration
    |--------------------------------------------------------------------------
    |
    | Creates a new customer account.
    |
    */

    Route::post('/customer-register', [
        AuthController::class,
        'customerRegister',
    ])
        ->name('auth.customer-register');


    /*
    |--------------------------------------------------------------------------
    | Customer Login
    |--------------------------------------------------------------------------
    |
    | Only users with the "customer" role can authenticate
    | through this endpoint.
    |
    */

    Route::post('/customer-login', [
        AuthController::class,
        'customerLogin',
    ])
        ->name('auth.customer-login');


    /*
    |--------------------------------------------------------------------------
    | Admin / Staff Login
    |--------------------------------------------------------------------------
    |
    | Existing administrator authentication.
    |
    */

    Route::post('/login', [
        AuthController::class,
        'login',
    ])
        ->name('auth.login');


    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Current User
        |--------------------------------------------------------------------------
        */

        Route::get('/me', [
            AuthController::class,
            'me',
        ])
            ->name('auth.me');


        /*
        |--------------------------------------------------------------------------
        | Update Own Profile
        |--------------------------------------------------------------------------
        */

        Route::put('/profile', [
            AuthController::class,
            'updateProfile',
        ])
            ->name('auth.profile.update');


        /*
        |--------------------------------------------------------------------------
        | Logout
        |--------------------------------------------------------------------------
        */

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ])
            ->name('auth.logout');
    });
});