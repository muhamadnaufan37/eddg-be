<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'tbl_pekerjaan';

    protected $fillable = [
        'nama_pekerjaan',
        'created_at',
    ];
}
