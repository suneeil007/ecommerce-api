<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All module API routes are loaded from this file.
|
*/

$modules = [
    'Auth',
    'Role',
    'Permission',
    'Category',
    'Product',
    'Attribute',
    'Cart',
    'Order',
    'Checkout',
    'Payment',
    'Coupon',
    'Profile',
    'Location',
    'User',
];

foreach ($modules as $module) {

    $route = base_path(
        "app/Modules/{$module}/routes/api.php"
    );

    if (file_exists($route)) {
        require $route;
    }
}
