<?php

use Illuminate\Support\Facades\Route;

use App\Modules\Coupon\Controllers\CouponController;

/*
|--------------------------------------------------------------------------
| Coupon Routes
|--------------------------------------------------------------------------
|
| Admin coupon management routes.
|
*/

Route::middleware([
    'auth:sanctum',
    'permission:coupons.view',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin - List
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/admin/coupons',
        [CouponController::class, 'adminIndex']
    )->name('admin.coupons.index');

    /*
    |--------------------------------------------------------------------------
    | Admin - Show
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/admin/coupons/{uuid}',
        [CouponController::class, 'adminShow']
    )->name('admin.coupons.show');
});


/*
|--------------------------------------------------------------------------
| Admin - Create
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'permission:coupons.create',
])->group(function () {

    Route::post(
        '/admin/coupons',
        [CouponController::class, 'store']
    )->name('admin.coupons.store');
});


/*
|--------------------------------------------------------------------------
| Admin - Update
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'permission:coupons.update',
])->group(function () {

    Route::put(
        '/admin/coupons/{uuid}',
        [CouponController::class, 'update']
    )->name('admin.coupons.update');
});


/*
|--------------------------------------------------------------------------
| Admin - Delete
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'permission:coupons.delete',
])->group(function () {

    Route::delete(
        '/admin/coupons/{uuid}',
        [CouponController::class, 'destroy']
    )->name('admin.coupons.destroy');
});


/*
|--------------------------------------------------------------------------
| Customer - Apply Coupon
|--------------------------------------------------------------------------
|
| This endpoint intentionally does not require authentication because
| coupons must also support guest customers.
|
*/

Route::post(
    '/coupons/apply',
    [CouponController::class, 'apply']
)->name('coupons.apply');

