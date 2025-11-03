<?php
// app/Models/JenisBantuan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisBantuan extends Model
{
    use HasFactory;

    protected $table = 'jenis_bantuan';
    protected $primaryKey = 'idjenisbantuan';

    protected $fillable = [
        'namajenisbantuan',
    ];

    public $timestamps = false;

    // Relasi ke sub_jenis_bantuan
    public function subJenisBantuan()
    {
        return $this->hasMany(SubJenisBantuan::class, 'idjenisbantuan', 'idjenisbantuan');
    }

    // Relasi ke kategori
    public function kategori()
    {
        return $this->hasMany(Kategori::class, 'idjenisbantuan', 'idjenisbantuan');
    }
}
