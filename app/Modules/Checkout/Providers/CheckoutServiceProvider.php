<?php namespace App\Modules\Checkout\Providers; 

use Illuminate\Support\ServiceProvider; 
use App\Modules\Checkout\Repositories\CheckoutRepository; 
use App\Modules\Checkout\Repositories\Contracts\CheckoutRepositoryInterface; 

class CheckoutServiceProvider extends ServiceProvider { 

    public function register(): void 
    { $this->app->bind( 
        CheckoutRepositoryInterface::class, 
        CheckoutRepository::class 
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
