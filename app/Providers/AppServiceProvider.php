<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | MySQL Index Length
        |--------------------------------------------------------------------------
        |
        | Some MySQL/MariaDB configurations have a maximum index length
        | smaller than Laravel's default 255-character utf8mb4 string.
        |
        */

        Schema::defaultStringLength(191);
    }
}
