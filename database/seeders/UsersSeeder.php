<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleSuperadmin = Role::where('name', 'Superadmin')->first();
        $Superadmin = User::create([
            'email' => 'superadmin@gmail.com',
            'email_verified_at' => now()->format('Y-m-d H:i:s'),
            'password' => bcrypt('root'),
            'username' => 'superadmin',
            'nama_lengkap' => 'Superadmin',
            'status' => 1,
            'role_id' => 1,
        ]);

        $rolePtgsData = Role::where('name', 'ptgs-data')->first();
        $ptgsData = User::create([
            'email' => 'ptgsdata01@gmail.com',
            'email_verified_at' => now()->format('Y-m-d H:i:s'),
            'password' => bcrypt('root'),
            'username' => 'ptgsdata01',
            'nama_lengkap' => 'Petugas Data 01',
            'status' => 1,
            'role_id' => 2,
        ]);

        $Superadmin->assignRole($roleSuperadmin);
        $ptgsData->assignRole($rolePtgsData);
    }
}
