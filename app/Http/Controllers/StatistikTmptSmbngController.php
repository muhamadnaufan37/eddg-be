<?php

namespace App\Http\Controllers;

use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\User;

class StatistikTmptSmbngController extends Controller
{
    public function data_tempat_sambung()
    {
        $table_daerah = dataDaerah::select(['id', 'nama_daerah'])
            ->groupBy('id', 'nama_daerah') // Menambahkan id ke dalam grup by
            ->orderBy('nama_daerah')
            ->get();

        $table_desa = dataDesa::select(['id', 'nama_desa'])
            ->groupBy('id', 'nama_desa') // Menambahkan id ke dalam grup by
            ->orderBy('nama_desa')
            ->get();

        $table_kelompok = dataKelompok::select(['id', 'nama_kelompok'])
            ->groupBy('id', 'nama_kelompok') // Menambahkan id ke dalam grup by
            ->orderBy('nama_kelompok')
            ->get();

        $table_user = User::select(['id', 'nama_lengkap'])
            ->groupBy('id', 'nama_lengkap') // Menambahkan id ke dalam grup by
            ->orderBy('nama_lengkap')
            ->get();

        $total_daerah = $table_daerah->count(); // Menghitung total jumlah daerah
        $total_desa = $table_desa->count(); // Menghitung total jumlah desa
        $total_kelompok = $table_kelompok->count(); // Menghitung total jumlah desa
        $total_user = $table_user->count(); // Menghitung total jumlah desa

        return response()->json([
            'message' => 'Sukses',
            'total_user' => $total_user,
            'total_data_tempat_sambung' => [
                'total_daerah' => $total_daerah,
                'total_desa' => $total_desa,
                'total_kelompok' => $total_kelompok,
            ],
            'success' => true,
        ], 200);
    }
}
