<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Modules\Role\Providers\RoleServiceProvider::class,
    App\Modules\Permission\Providers\PermissionServiceProvider::class,
    App\Modules\Auth\Providers\AuthServiceProvider::class,
    App\Modules\Category\Providers\CategoryServiceProvider::class,
    App\Modules\Product\Providers\ProductServiceProvider::class,
    App\Modules\Attribute\Providers\AttributeServiceProvider::class,
    App\Modules\Cart\Providers\CartServiceProvider::class,
    App\Modules\Order\Providers\OrderServiceProvider::class,
    App\Modules\Checkout\Providers\CheckoutServiceProvider::class,
    App\Modules\Payment\Providers\PaymentServiceProvider::class,
    App\Modules\Coupon\Providers\CouponServiceProvider::class,
    App\Modules\Profile\Providers\ProfileServiceProvider::class,
    App\Modules\User\Providers\UserServiceProvider::class,
];