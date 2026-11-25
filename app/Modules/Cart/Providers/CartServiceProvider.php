<?php

namespace App\Modules\Cart\Providers;

use App\Modules\Cart\Repositories\CartRepository;
use App\Modules\Cart\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        $this->app->bind(
            CartRepositoryInterface::class,
            CartRepository::class
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

