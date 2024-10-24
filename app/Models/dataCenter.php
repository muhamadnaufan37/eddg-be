<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataCenter extends Model
{
    use HasFactory;

    protected $table = 'config';
    public $timestamps = false;
    public $incrementing = false;

    protected $guarded = ['id'];
}
