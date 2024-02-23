<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataDesa extends Model
{
    use HasFactory;

    protected $table = 'tabel_desa';

    protected $fillable = [
        'nama_desa',
    ];
}
