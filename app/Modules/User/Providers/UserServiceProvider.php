<?php

namespace App\Modules\User\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
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
