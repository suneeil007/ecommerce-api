<?php

use App\Modules\Permission\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Permission API Routes
|--------------------------------------------------------------------------
|
| Authentication:
|   auth:sanctum
|
| Authorization:
|   permission:permissions.*
|
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->prefix('permissions')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | View Permissions
        |--------------------------------------------------------------------------
        */

        Route::get('/', [
            PermissionController::class,
            'index',
        ])
            ->middleware('permission:permissions.view')
            ->name('permissions.index');

        /*
        |--------------------------------------------------------------------------
        | Create Permission
        |--------------------------------------------------------------------------
        */

        Route::post('/', [
            PermissionController::class,
            'store',
        ])
            ->middleware('permission:permissions.create')
            ->name('permissions.store');

        /*
        |--------------------------------------------------------------------------
        | View Single Permission
        |--------------------------------------------------------------------------
        */

        Route::get('/{permission}', [
            PermissionController::class,
            'show',
        ])
            ->middleware('permission:permissions.view')
            ->name('permissions.show');

        /*
        |--------------------------------------------------------------------------
        | Update Permission
        |--------------------------------------------------------------------------
        */

        Route::put('/{permission}', [
            PermissionController::class,
            'update',
        ])
            ->middleware('permission:permissions.update')
            ->name('permissions.update');

        /*
        |--------------------------------------------------------------------------
        | Delete Permission
        |--------------------------------------------------------------------------
        */

        Route::delete('/{permission}', [
            PermissionController::class,
            'destroy',
        ])
            ->middleware('permission:permissions.delete')
            ->name('permissions.destroy');
    });

