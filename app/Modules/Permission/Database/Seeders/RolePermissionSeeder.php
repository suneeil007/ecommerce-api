<?php

namespace App\Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Role\Models\Role;
use App\Modules\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        | Super Admin gets every permission.
        */

        $superAdmin = Role::where('name', 'super_admin')->first();

        if ($superAdmin) {
            $superAdmin->permissions()->sync(
                Permission::pluck('id')->toArray()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $permissions = Permission::whereIn('name', [

                // Users
                'users.view',
                'users.create',
                'users.update',
                'users.delete',

                // Products
                'products.view',
                'products.create',
                'products.update',
                'products.delete',

                // Categories
                'categories.view',
                'categories.create',
                'categories.update',
                'categories.delete',

                // Orders
                'orders.view',
                'orders.create',
                'orders.update',
                'orders.delete',

                // Payments
                'payments.view',

                // Roles
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',

                // Attributes
                'attributes.view',
                'attributes.create',
                'attributes.update',
                'attributes.delete',

                // Permissions
                'permissions.view',

            ])->pluck('id')->toArray();

            $admin->permissions()->sync($permissions);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customer = Role::where('name', 'customer')->first();

        if ($customer) {
            $permissions = Permission::whereIn('name', [
                'products.view',
                'categories.view',
                'orders.view',
                'orders.create',
            ])->pluck('id')->toArray();

            $customer->permissions()->sync($permissions);
        }
    }
}
