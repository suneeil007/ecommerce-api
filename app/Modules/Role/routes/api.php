<?php

use App\Modules\Role\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Role API Routes
|--------------------------------------------------------------------------
|
| Authentication:
|   auth:sanctum
|
| Authorization:
|   permission:roles.*
|
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->prefix('roles')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | View Roles
        |--------------------------------------------------------------------------
        */

        Route::get('/', [
            RoleController::class,
            'index',
        ])
            ->middleware('permission:roles.view')
            ->name('roles.index');

        /*
        |--------------------------------------------------------------------------
        | Create Role
        |--------------------------------------------------------------------------
        */

        Route::post('/', [
            RoleController::class,
            'store',
        ])
            ->middleware('permission:roles.create')
            ->name('roles.store');

        /*
        |--------------------------------------------------------------------------
        | View Single Role
        |--------------------------------------------------------------------------
        */

        Route::get('/{role}', [
            RoleController::class,
            'show',
        ])
            ->middleware('permission:roles.view')
            ->name('roles.show');

        /*
        |--------------------------------------------------------------------------
        | Update Role
        |--------------------------------------------------------------------------
        */

        Route::put('/{role}', [
            RoleController::class,
            'update',
        ])
            ->middleware('permission:roles.update')
            ->name('roles.update');

        /*
        |--------------------------------------------------------------------------
        | Delete Role
        |--------------------------------------------------------------------------
        */

        Route::delete('/{role}', [
            RoleController::class,
            'destroy',
        ])
            ->middleware('permission:roles.delete')
            ->name('roles.destroy');
    });

