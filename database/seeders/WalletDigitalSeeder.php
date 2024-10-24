<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletDigitalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('test_wallet_digital')->insert([
            'id' => Str::uuid()->toString(),
            'id_user' => 1,
            'order_id' => 'INV-00001',
            'jenis_transaksi' => 'Tampungan Kas 2',
            'keterangan' => 'test',
            'amount' => 50000000,
        ]);
    }
}
