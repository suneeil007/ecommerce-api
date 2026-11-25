<?php

use App\Modules\Checkout\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('checkout')
    ->middleware('auth:sanctum')
    ->group(function () {

        // Create checkout from active cart
        Route::post('/', [
            CheckoutController::class,
            'store'
        ]);

        // Get authenticated user's checkouts
        Route::get('/', [
            CheckoutController::class,
            'index'
        ]);

        // Get single checkout
        Route::get('/{uuid}', [
            CheckoutController::class,
            'show'
        ]);
    });

