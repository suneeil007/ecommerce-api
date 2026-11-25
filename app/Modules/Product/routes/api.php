<?php

use Illuminate\Support\Facades\Route;

use App\Modules\Product\Controllers\ProductController;
use App\Modules\Product\Controllers\ProductVariantController;
use App\Modules\Product\Controllers\ProductDescriptionImageController;


/*
|--------------------------------------------------------------------------
| FRONTEND / CUSTOMER
|--------------------------------------------------------------------------
*/

Route::prefix('products')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Product Listing
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/',
        [ProductController::class, 'index']
    );


    /*
    |--------------------------------------------------------------------------
    | Featured Products
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/featured',
        [ProductController::class, 'featured']
    );


    /*
    |--------------------------------------------------------------------------
    | Product Detail by Slug
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/{slug}',
        [ProductController::class, 'showBySlug']
    );
});


/*
|--------------------------------------------------------------------------
| ADMIN PRODUCTS
|--------------------------------------------------------------------------
*/

Route::prefix('admin/products')
    ->middleware('auth:sanctum')
    ->group(function () {

        Route::post(
            '/description-images',
            [ProductDescriptionImageController::class, 'store']
        );


        /*
        |--------------------------------------------------------------------------
        | View Products
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/',
            [ProductController::class, 'adminIndex']
        )->middleware('permission:products.view');


        Route::get(
            '/{uuid}',
            [ProductController::class, 'adminShow']
        )->middleware('permission:products.view');


        /*
        |--------------------------------------------------------------------------
        | Create Product
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/',
            [ProductController::class, 'store']
        )->middleware('permission:products.create');


        /*
        |--------------------------------------------------------------------------
        | Update Product
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/{uuid}',
            [ProductController::class, 'update']
        )->middleware('permission:products.update');


        Route::patch(
            '/{uuid}',
            [ProductController::class, 'update']
        )->middleware('permission:products.update');


        /*
        |--------------------------------------------------------------------------
        | Delete Product
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/{uuid}',
            [ProductController::class, 'destroy']
        )->middleware('permission:products.delete');


        /*
        |--------------------------------------------------------------------------
        | PRODUCT VARIANTS
        |--------------------------------------------------------------------------
        |
        | Variants are managed independently from the main product update.
        |
        | This prevents updating one variant from accidentally deleting
        | the other variants.
        |
        */


        /*
        |--------------------------------------------------------------------------
        | List Product Variants
        |--------------------------------------------------------------------------
        |
        | GET /api/admin/products/{uuid}/variants
        |
        */

        Route::get(
            '/{uuid}/variants',
            [ProductVariantController::class, 'index']
        )->middleware('permission:products.view');


        /*
        |--------------------------------------------------------------------------
        | Create Product Variant
        |--------------------------------------------------------------------------
        |
        | POST /api/admin/products/{uuid}/variants
        |
        */

        Route::post(
            '/{uuid}/variants',
            [ProductVariantController::class, 'store']
        )->middleware('permission:products.create');


        /*
        |--------------------------------------------------------------------------
        | Show Individual Variant
        |--------------------------------------------------------------------------
        |
        | GET /api/admin/products/{uuid}/variants/{variant}
        |
        */

        Route::get(
            '/{uuid}/variants/{variant}',
            [ProductVariantController::class, 'show']
        )->middleware('permission:products.view');


        /*
        |--------------------------------------------------------------------------
        | Update Individual Variant
        |--------------------------------------------------------------------------
        |
        | PUT /api/admin/products/{uuid}/variants/{variant}
        |
        */

        Route::put(
            '/{uuid}/variants/{variant}',
            [ProductVariantController::class, 'update']
        )->middleware('permission:products.update');


        /*
        |--------------------------------------------------------------------------
        | Delete Individual Variant
        |--------------------------------------------------------------------------
        |
        | DELETE /api/admin/products/{uuid}/variants/{variant}
        |
        */

        Route::delete(
            '/{uuid}/variants/{variant}',
            [ProductVariantController::class, 'destroy']
        )->middleware('permission:products.delete');


        /*
        |--------------------------------------------------------------------------
        | Update Variant Option Values
        |--------------------------------------------------------------------------
        |
        | PUT /api/admin/products/{uuid}/variants/{variant}/options
        |
        */

        Route::put(
            '/{uuid}/variants/{variant}/options',
            [ProductVariantController::class, 'updateOptions']
        )->middleware('permission:products.update');
    });

