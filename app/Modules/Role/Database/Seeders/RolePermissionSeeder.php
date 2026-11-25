<?php

namespace App\Modules\Role\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Role\Models\Role;
use App\Modules\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        | Super admin gets all permissions.
        |--------------------------------------------------------------------------
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
            $adminPermissions = Permission::whereIn('name', [
                'users.view',
                'users.create',
                'users.update',

                'products.view',
                'products.create',
                'products.update',
                'products.delete',

                'categories.view',
                'categories.create',
                'categories.update',
                'categories.delete',

                'orders.view',
                'orders.create',
                'orders.update',
                'orders.delete',

                'roles.view',
                'roles.create',
                'roles.update',

                'permissions.view',
            ])->pluck('id')->toArray();

            $admin->permissions()->sync($adminPermissions);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customer = Role::where('name', 'customer')->first();

        if ($customer) {
            $customerPermissions = Permission::whereIn('name', [
                'products.view',
                'categories.view',
                'orders.view',
                'orders.create',
            ])->pluck('id')->toArray();

            $customer->permissions()->sync($customerPermissions);
        }
    }
}

