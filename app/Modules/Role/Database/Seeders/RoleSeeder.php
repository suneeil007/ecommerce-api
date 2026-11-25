<?php

namespace App\Modules\Role\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Role\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [

            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Full system access.',
            ],

            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access.',
            ],

            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Management access.',
            ],

            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Customer access.',
            ],

        ];

        foreach ($roles as $role) {

            Role::updateOrCreate(
                [
                    'name' => $role['name'],
                ],
                $role
            );
        }
    }
}

