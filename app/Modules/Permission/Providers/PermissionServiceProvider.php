<?php

namespace App\Modules\Permission\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Permission\Repositories\PermissionRepository; 
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void { 
        $this->app->bind( 
            PermissionRepositoryInterface::class, 
            PermissionRepository::class 
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
