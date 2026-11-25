
<?php

use App\Modules\Payment\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Payment Routes
|--------------------------------------------------------------------------
*/

Route::prefix('payments')
    ->middleware('auth:sanctum')
    ->group(function () {

        Route::post(
            '/',
            [PaymentController::class, 'store']
        );

        Route::get(
            '/',
            [PaymentController::class, 'index']
        );

        Route::post(
            '/esewa/initiate',
            [PaymentController::class, 'initiateEsewa']
        );

        Route::get(
            '/{uuid}',
            [PaymentController::class, 'show']
        );
    });

/*
|--------------------------------------------------------------------------
| Admin Payment Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/payments')
    ->middleware([
        'auth:sanctum',
        'permission:payments.view',
    ])
    ->group(function () {

        Route::get(
            '/',
            [PaymentController::class, 'adminIndex']
        );

        Route::get(
            '/statistics',
            [PaymentController::class, 'adminStatistics']
        );

        Route::get(
            '/{uuid}',
            [PaymentController::class, 'adminShow']
        );
    });
