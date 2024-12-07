<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataSensusPeserta extends Model
{
    use HasFactory;

    protected $table = 'data_peserta';

    protected $fillable = [
        'kode_cari_data',
        'nama_lengkap',
        'nama_panggilan',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'jenis_kelamin',
        'no_telepon',
        'nama_ayah',
        'nama_ibu',
        'hoby',
        'pekerjaan',
        'usia_menikah',
        'kriteria_pasangan',
        'status_pernikahan',
        'status_sambung',
        'status_kelas',
        'status_atlet_asad',
        'tmpt_daerah',
        'tmpt_desa',
        'tmpt_kelompok',
        'jenis_data',
        'user_id',
        'img',
        'created_at',
    ];
}
