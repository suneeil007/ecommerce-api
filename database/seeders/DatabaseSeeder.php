<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Role\Database\Seeders\RoleSeeder;
use App\Modules\Permission\Database\Seeders\PermissionSeeder;
use App\Modules\Role\Database\Seeders\RolePermissionSeeder;
use App\Modules\Role\Database\Seeders\UserRoleSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            /*
             * 1. Create roles
             */
            RoleSeeder::class,

            /*
             * 2. Create permissions
             */
            PermissionSeeder::class,

            /*
             * 3. Connect roles → permissions
             */
            RolePermissionSeeder::class,

            /*
             * 4. Create users and connect users → roles
             */
            UserRoleSeeder::class,
        ]);
    }
}

