<?php

namespace App\Modules\Product\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Repositories\Contracts\ProductRepositoryInterface;


class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void { 
        $this->app->bind( 
            ProductRepositoryInterface::class, 
            ProductRepository::class 
            ); 
    }

    /**
     * Bootstrap module services.
     */
    public function boot(): void
    {
        // $this->loadRoutesFrom(
        //     __DIR__ . '/../routes/api.php'
        // );

        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}
