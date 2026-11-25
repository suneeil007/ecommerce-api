<?php

namespace App\Modules\Role\Database\Seeders;

use App\Models\User;
use App\Modules\Role\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $superAdminRole = Role::where('name', 'super_admin')->first();

        if ($superAdminRole) {

            $user = User::updateOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password'),
                ]
            );

            $user->roles()->syncWithoutDetaching([
                $superAdminRole->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {

            $user = User::updateOrCreate(
                ['email' => 'manager@example.com'],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make('password'),
                ]
            );

            $user->roles()->syncWithoutDetaching([
                $adminRole->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customerRole = Role::where('name', 'customer')->first();

        if ($customerRole) {

            $user = User::updateOrCreate(
                ['email' => 'customer@example.com'],
                [
                    'name' => 'Customer User',
                    'password' => Hash::make('password'),
                ]
            );

            $user->roles()->syncWithoutDetaching([
                $customerRole->id,
            ]);
        }
    }
}

