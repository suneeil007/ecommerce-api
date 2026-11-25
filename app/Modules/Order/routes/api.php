
<?php

use App\Modules\Order\Controllers\OrderController;
use App\Modules\Order\Controllers\AdminOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Order Routes
|--------------------------------------------------------------------------
*/

Route::prefix('orders')->group(function () {

    // Guest checkout
    Route::post('/', [OrderController::class, 'store']);

    // Authenticated customer routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [OrderController::class, 'index']);

        Route::post(
            '/from-checkout/{checkoutUuid}',
            [OrderController::class, 'storeFromCheckout']
        );

        Route::get('/{uuid}', [OrderController::class, 'show']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Order Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware([
        'auth:sanctum',
        'role:super_admin,admin',
    ])
    ->group(function () {
        
        // View orders
        Route::middleware('permission:orders.view')->group(function () {
            Route::get('/orders', [AdminOrderController::class, 'index']);

            Route::get('/orders/{uuid}', [AdminOrderController::class, 'show']);
        });

        // Update order status
        Route::patch(
            '/orders/{uuid}/status',
            [AdminOrderController::class, 'updateStatus']
        )->middleware('permission:orders.update');

        // Create shipment
        Route::post(
            '/orders/{uuid}/shipments',
            [AdminOrderController::class, 'storeShipment']
        )->middleware('permission:orders.update');

        // Add tracking event
        Route::post(
            '/shipments/{uuid}/events',
            [AdminOrderController::class, 'storeShipmentEvent']
        )->middleware('permission:orders.update');
    });