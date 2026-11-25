<?php

use App\Modules\Cart\Controllers\CartController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cart Module API Routes
|--------------------------------------------------------------------------
|
| These routes support both:
| - Authenticated users through Laravel Sanctum
| - Guest users through X-Cart-Session
|
*/

Route::prefix('cart')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Get Current Cart
        |--------------------------------------------------------------------------
        |
        | GET /api/cart
        |
        */

        Route::get('/', [
            CartController::class,
            'index',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Add Item
        |--------------------------------------------------------------------------
        |
        | POST /api/cart/items
        |
        */

        Route::post('/items', [
            CartController::class,
            'store',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Item
        |--------------------------------------------------------------------------
        |
        | PUT /api/cart/items/{item}
        |
        */

        Route::put('/items/{item}', [
            CartController::class,
            'update',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Item - PATCH
        |--------------------------------------------------------------------------
        |
        | PATCH /api/cart/items/{item}
        |
        */

        Route::patch('/items/{item}', [
            CartController::class,
            'update',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Remove Item
        |--------------------------------------------------------------------------
        |
        | DELETE /api/cart/items/{item}
        |
        */

        Route::delete('/items/{item}', [
            CartController::class,
            'destroy',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Clear Cart
        |--------------------------------------------------------------------------
        |
        | DELETE /api/cart
        |
        */

        Route::delete('/', [
            CartController::class,
            'clear',
        ]);
    });

