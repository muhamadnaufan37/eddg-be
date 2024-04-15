<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblPesertaDidik extends Model
{
    use HasFactory;

    protected $table = 'peserta_didik';

    protected $fillable = [
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'status_keluarga',
        'hoby',
        'anak_ke',
        'nama_ayah',
        'pekerjaan_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'no_telepon_org_tua',
        'nama_wali',
        'pekerjaan_wali',
        'no_telepon_wali',
        'alamat',
        'status_peserta_didik',
        'add_by_user_id',
        'tmpt_daerah',
        'tmpt_desa',
        'tmpt_kelompok',
        'created_at',
    ];
}
