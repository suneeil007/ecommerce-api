<?php

namespace App\Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Users
            ['name' => 'users.view', 'display_name' => 'View Users'],
            ['name' => 'users.create', 'display_name' => 'Create Users'],
            ['name' => 'users.update', 'display_name' => 'Update Users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users'],

            // Products
            ['name' => 'products.view', 'display_name' => 'View Products'],
            ['name' => 'products.create', 'display_name' => 'Create Products'],
            ['name' => 'products.update', 'display_name' => 'Update Products'],
            ['name' => 'products.delete', 'display_name' => 'Delete Products'],

            // Categories
            ['name' => 'categories.view', 'display_name' => 'View Categories'],
            ['name' => 'categories.create', 'display_name' => 'Create Categories'],
            ['name' => 'categories.update', 'display_name' => 'Update Categories'],
            ['name' => 'categories.delete', 'display_name' => 'Delete Categories'],

            // Orders
            ['name' => 'orders.view', 'display_name' => 'View Orders'],
            ['name' => 'orders.create', 'display_name' => 'Create Orders'],
            ['name' => 'orders.update', 'display_name' => 'Update Orders'],
            ['name' => 'orders.delete', 'display_name' => 'Delete Orders'],

            // Payments
            ['name' => 'payments.view', 'display_name' => 'View Payments'],

            // Roles
            ['name' => 'roles.view', 'display_name' => 'View Roles'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles'],
            ['name' => 'roles.update', 'display_name' => 'Update Roles'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles'],

            // Permissions
            ['name' => 'permissions.view', 'display_name' => 'View Permissions'],
            ['name' => 'permissions.create', 'display_name' => 'Create Permissions'],
            ['name' => 'permissions.update', 'display_name' => 'Update Permissions'],
            ['name' => 'permissions.delete', 'display_name' => 'Delete Permissions'],

            // Attributes
            ['name' => 'attributes.view', 'display_name' => 'View Attributes'],
            ['name' => 'attributes.create', 'display_name' => 'Create Attributes'],
            ['name' => 'attributes.update', 'display_name' => 'Update Attributes'],
            ['name' => 'attributes.delete', 'display_name' => 'Delete Attributes'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
