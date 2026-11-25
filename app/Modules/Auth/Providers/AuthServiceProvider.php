<?php

namespace App\Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Auth\Repositories\AuthRepository; 
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
       $this->app->bind( 
            AuthRepositoryInterface::class, 
            AuthRepository::class 
        );
    }

    /**
     * Bootstrap module services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(
            __DIR__ . '/../routes/api.php'
        );

        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}
