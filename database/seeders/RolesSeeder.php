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
            'name' => 'Petugas Data Sensus',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas Pendataan Sensus',
        ]);

        Role::create([
            'name' => 'Bendahara / KU',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas Pengelolaan Keuangan',
        ]);

        Role::create([
            'name' => 'Admin KBM',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas Pengelolaan Data PPG',
        ]);
    }
}
