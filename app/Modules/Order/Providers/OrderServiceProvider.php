<?php

namespace App\Modules\Order\Providers;

use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );
    }

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

