
<?php

use App\Modules\User\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('admin/users')
    ->group(function () {

        Route::get('/', [
            AdminUserController::class,
            'index',
        ])
            ->middleware('permission:users.view')
            ->name('admin.users.index');

        Route::get('/{uuid}/orders', [
            AdminUserController::class,
            'orders',
        ])
            ->middleware('permission:users.view')
            ->name('admin.users.orders');

        Route::get('/{uuid}', [
            AdminUserController::class,
            'show',
        ])
            ->middleware('permission:users.view')
            ->name('admin.users.show');

        Route::put('/{uuid}', [
            AdminUserController::class,
            'update',
        ])
            ->middleware('permission:users.update')
            ->name('admin.users.update');

        Route::patch('/{uuid}/status', [
            AdminUserController::class,
            'updateStatus',
        ])
            ->middleware('permission:users.update')
            ->name('admin.users.status');

        Route::delete('/{uuid}', [
            AdminUserController::class,
            'destroy',
        ])
            ->middleware('permission:users.update')
            ->name('admin.users.destroy');
    });
