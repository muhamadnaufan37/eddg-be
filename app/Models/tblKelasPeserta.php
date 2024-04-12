<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblKelasPeserta extends Model
{
    use HasFactory;

    protected $table = 'kelas_peserta_didik';

    protected $fillable = [
        'nama_kelas',
        'created_at',
    ];
}
