<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblCppdb extends Model
{
    use HasFactory;

    protected $table = 'cppdb';

    protected $fillable = [
        'id',
        'kode_cari_ppdb',
        'id_thn_akademik',
        'id_kelas',
        'id_pengajar',
        'id_peserta',
        'id_petugas',
        'nilai1',
        'nilai2',
        'nilai3',
        'nilai4',
        'nilai5',
        'nilai6',
        'nilai7',
        'nilai8',
        'nilai9',
        'nilai10',
        'nilai11',
        'nilai12',
        'nilai13',
        'nilai14',
        'nilai15',
        'nilai16',
        'nilai_presensi_1',
        'nilai_presensi_2',
        'nilai_presensi_3',
        'catatan_ortu',
        'tmpt_penetapan',
        'status_naik_kelas',
        'created_at',
    ];
}
