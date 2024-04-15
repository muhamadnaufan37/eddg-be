<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'Superadmin',
            'guard_name' => 'web',
            'description' => 'Untuk Role Superadmin',
        ]);

        Role::create([
            'name' => 'ptgs-data',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugaas Pendataan Sensus',
        ]);
    }
}
