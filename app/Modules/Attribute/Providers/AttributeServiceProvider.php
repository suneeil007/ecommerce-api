<?php

namespace App\Modules\Attribute\Providers;
use App\Modules\Attribute\Repositories\Contracts\AttributeRepositoryInterface; 
use App\Modules\Attribute\Repositories\AttributeRepository;
use Illuminate\Support\ServiceProvider;

class AttributeServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void 
    { 
        $this->app->bind( 
            AttributeRepositoryInterface::class, 
            AttributeRepository::class 
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
