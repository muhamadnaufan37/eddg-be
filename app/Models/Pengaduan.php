<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaduan extends Model
{
    use HasFactory;

    protected $table = 'pengaduan';

    protected $fillable = [
        'uuid',
        'nama_lengkap',
        'kontak',
        'jenis_pengaduan',
        'subjek',
        'isi_pengaduan',
        'lampiran',
        'ip_address',
        'user_agent',
        'status_pengaduan',
        'nama_kelompok',
        'balasan_admin',
        'tanggal_dibalas',
        'dibalas_oleh',
    ];
}
