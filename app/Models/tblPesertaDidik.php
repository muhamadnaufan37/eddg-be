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
        'hoby',
        'nama_ortu',
        'no_telepon_nama_ortu',
        'alamat',
        'status_peserta_didik',
        'add_by_user_id',
        'tmpt_daerah',
        'tmpt_desa',
        'tmpt_kelompok',
        'created_at',
    ];
}
