<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblPengajar extends Model
{
    use HasFactory;

    protected $table = 'pengajar';

    protected $fillable = [
        'nama_pengajar',
        'status_pengajar',
        'add_by_user_id',
        'tmpt_daerah',
        'tmpt_desa',
        'tmpt_kelompok',
        'created_at',
    ];
}
