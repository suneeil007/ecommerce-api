<?php

use App\Modules\Category\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Category Routes
|--------------------------------------------------------------------------
*/

Route::prefix('categories')->group(function () {

    Route::get('/', [
        CategoryController::class,
        'index',
    ])->name('categories.index');

    Route::get('/{slug}', [
        CategoryController::class,
        'showBySlug',
    ])->name('categories.show');
});


/*
|--------------------------------------------------------------------------
| Admin Category Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/categories')
    ->middleware('auth:sanctum')
    ->group(function () {

        /*
        |----------------------------------------------------------------------
        | List
        |----------------------------------------------------------------------
        */

        Route::get('/', [
            CategoryController::class,
            'adminIndex',
        ])
        ->middleware('permission:categories.view')
        ->name('admin.categories.index');


        /*
        |----------------------------------------------------------------------
        | Create
        |----------------------------------------------------------------------
        */

        Route::post('/', [
            CategoryController::class,
            'store',
        ])
        ->middleware('permission:categories.create')
        ->name('admin.categories.store');


        /*
        |----------------------------------------------------------------------
        | Show
        |----------------------------------------------------------------------
        */

        Route::get('/{uuid}', [
            CategoryController::class,
            'adminShow',
        ])
        ->middleware('permission:categories.view')
        ->name('admin.categories.show');


        /*
        |----------------------------------------------------------------------
        | Update
        |----------------------------------------------------------------------
        */

        Route::put('/{uuid}', [
            CategoryController::class,
            'update',
        ])
        ->middleware('permission:categories.update')
        ->name('admin.categories.update');


        /*
        |----------------------------------------------------------------------
        | Upload/update category including image
        |----------------------------------------------------------------------
        */

        Route::post('/{uuid}/update', [
            CategoryController::class,
            'update',
        ])
        ->middleware('permission:categories.update')
        ->name('admin.categories.update.post');


        /*
        |----------------------------------------------------------------------
        | Delete
        |----------------------------------------------------------------------
        */

        Route::delete('/{uuid}', [
            CategoryController::class,
            'destroy',
        ])
        ->middleware('permission:categories.delete')
        ->name('admin.categories.destroy');
    });

