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
            'name' => 'Operator Sensus',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas Pendataan Sensus',
        ]);

        Role::create([
            'name' => 'Bendahara / KU',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas Pengelolaan Keuangan',
        ]);

        Role::create([
            'name' => 'Operator KBM',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas Pengelolaan Data PPG',
        ]);

        Role::create([
            'name' => 'Operator Admin Data Center',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas Pengelolaan Data Sensus',
        ]);

        Role::create([
            'name' => 'Pengurus',
            'guard_name' => 'web',
            'description' => 'Untuk Role pengurus',
        ]);

        Role::create([
            'name' => 'Operator Presensi',
            'guard_name' => 'web',
            'description' => 'Untuk Role Petugas absensi',
        ]);
    }
}
