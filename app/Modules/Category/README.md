# Category Module



D:\SERVER\www\ecommerce-api>php artisan module:make Category
Module [Category] created successfully.

D:\SERVER\www\ecommerce-api>php artisan module:make-migration Permission create_permission_role_table



app/Modules/Category/
├── Controllers/
│   └── CategoryController.php
│
├── Models/
│   └── Category.php
│
├── Requests/
│   ├── StoreCategoryRequest.php
│   └── UpdateCategoryRequest.php
│
├── Repositories/
│   ├── Contracts/
│   │   └── CategoryRepositoryInterface.php
│   └── CategoryRepository.php
│
├── Services/
│   └── CategoryService.php
│
├── Resources/
│   └── CategoryResource.php
│
├── Routes/
│   └── api.php
│
├── Database/
│   └── Migrations/
│       ├── xxxx_create_categories_table.php
│       └── xxxx_add_uuid_to_categories_table.php
│
└── Providers/
    └── CategoryServiceProvider.php