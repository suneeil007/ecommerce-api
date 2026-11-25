<?php

namespace App\Modules\Role\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Role\Repositories\RoleRepository;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;

class RoleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}

