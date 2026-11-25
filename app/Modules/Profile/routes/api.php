<?php

use App\Modules\Profile\Controllers\AddressController;
use App\Modules\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Profile API Routes
|--------------------------------------------------------------------------
|
| All routes in this module require an authenticated customer.
|
*/

Route::middleware('auth:sanctum')->prefix('customer')->group(
    function () {
        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/profile',
            [ProfileController::class, 'show']
        )->name('customer.profile.show');

        Route::put(
            '/profile',
            [ProfileController::class, 'update']
        )->name('customer.profile.update');

        /*
        |--------------------------------------------------------------------------
        | Addresses
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/addresses',
            [AddressController::class, 'index']
        )->name('customer.addresses.index');

        Route::post(
            '/addresses',
            [AddressController::class, 'store']
        )->name('customer.addresses.store');

        Route::get(
            '/addresses/{uuid}',
            [AddressController::class, 'show']
        )->name('customer.addresses.show');

        Route::put(
            '/addresses/{uuid}',
            [AddressController::class, 'update']
        )->name('customer.addresses.update');

        Route::patch(
            '/addresses/{uuid}/default',
            [AddressController::class, 'setDefault']
        )->name('customer.addresses.default');

        Route::delete(
            '/addresses/{uuid}',
            [AddressController::class, 'destroy']
        )->name('customer.addresses.destroy');
    }
);
