<?php

namespace App\Modules\Profile\Providers;

use App\Modules\Profile\Repositories\AddressRepository;
use App\Modules\Profile\Repositories\Contracts\AddressRepositoryInterface;
use App\Modules\Profile\Repositories\Contracts\ProfileRepositoryInterface;
use App\Modules\Profile\Repositories\ProfileRepository;
use Illuminate\Support\ServiceProvider;

class ProfileServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Repository Bindings
        |--------------------------------------------------------------------------
        */

        $this->app->bind(
            ProfileRepositoryInterface::class,
            ProfileRepository::class
        );

        $this->app->bind(
            AddressRepositoryInterface::class,
            AddressRepository::class
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
