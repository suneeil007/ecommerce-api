# Cart Module

Project uses the module-based Artisan commands

php artisan module:make Cart
php artisan module:make-migration Cart create_carts_table



Cart
├── Controllers
│   └── CartController.php
│
├── Models
│   ├── Cart.php
│   └── CartItem.php
│
├── Requests
│   ├── StoreCartRequest.php
│   ├── UpdateCartRequest.php
│   ├── StoreCartItemRequest.php
│   └── UpdateCartItemRequest.php
│
├── Resources
│   ├── CartResource.php
│   └── CartItemResource.php
│
├── Services
│   └── CartService.php
│
├── Repositories
│   ├── CartRepository.php
│   └── Contracts
│       └── CartRepositoryInterface.php
│
├── Enums
├── Database
│   ├── Migrations
│   └── Seeders
├── Providers
│   └── CartServiceProvider.php
├── routes
│   └── api.php
├── config
│   └── cart.php
└── Helpers
    └── helpers.php
