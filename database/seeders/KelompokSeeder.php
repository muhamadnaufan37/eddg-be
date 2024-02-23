<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelompokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Barsi',
        ]);

        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Ciseureuh 1',
        ]);

        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Ciseureuh 2',
        ]);

        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Sukamulya 1',
        ]);

        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Sukamulya 2',
        ]);

        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Sukamulya 3',
        ]);

        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Cikembang 1',
        ]);

        DB::table('kelompok')->insert([
            'nama_kelompok' => 'Cikembang 2',
        ]);
    }
}
