<?php

use App\Modules\Attribute\Controllers\AttributeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/attributes')
    ->middleware('auth:sanctum')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Attributes
        |--------------------------------------------------------------------------
        */

        Route::get('/', [
            AttributeController::class,
            'index',
        ])->middleware('permission:attributes.view');

        Route::post('/', [
            AttributeController::class,
            'store',
        ])->middleware('permission:attributes.create');

        Route::get('/{uuid}', [
            AttributeController::class,
            'show',
        ])->middleware('permission:attributes.view');

        Route::put('/{uuid}', [
            AttributeController::class,
            'update',
        ])->middleware('permission:attributes.update');

        Route::patch('/{uuid}', [
            AttributeController::class,
            'update',
        ])->middleware('permission:attributes.update');

        Route::delete('/{uuid}', [
            AttributeController::class,
            'destroy',
        ])->middleware('permission:attributes.delete');

        /*
        |--------------------------------------------------------------------------
        | Attribute Values
        |--------------------------------------------------------------------------
        */

        Route::get('/{uuid}/values', [
            AttributeController::class,
            'values',
        ])->middleware('permission:attributes.view');

        Route::post('/{uuid}/values', [
            AttributeController::class,
            'storeValue',
        ])->middleware('permission:attributes.create');

        Route::put('/{uuid}/values/{valueUuid}', [
            AttributeController::class,
            'updateValue',
        ])->middleware('permission:attributes.update');

        Route::patch('/{uuid}/values/{valueUuid}', [
            AttributeController::class,
            'updateValue',
        ])->middleware('permission:attributes.update');

        Route::delete('/{uuid}/values/{valueUuid}', [
            AttributeController::class,
            'destroyValue',
        ])->middleware('permission:attributes.delete');
    });

