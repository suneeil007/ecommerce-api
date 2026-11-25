<?php

namespace App\Modules\Coupon\Providers;

use App\Modules\Coupon\Repositories\Contracts\CouponRepositoryInterface;
use App\Modules\Coupon\Repositories\CouponRepository;
use Illuminate\Support\ServiceProvider;

class CouponServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CouponRepositoryInterface::class,
            CouponRepository::class
        );
    }

    public function boot(): void
    {
        //
        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}