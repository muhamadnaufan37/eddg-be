<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataCenter extends Model
{
    use HasFactory;

    protected $table = 'config';

    protected $fillable = [
        'config_name',
        'config_comment',
        'config_status',
    ];
}
