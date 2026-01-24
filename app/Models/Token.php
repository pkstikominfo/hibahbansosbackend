<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;

    protected $table = 'tb_token';

    protected $fillable = [
        'source',
        'token',
        'nama',
        'status',
    ];

    public $timestamps = true;
}
