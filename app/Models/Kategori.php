<?php
// app/Models/Kategori.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';
    protected $primaryKey = 'idkategori';

    protected $fillable = [
        'idjenisbantuan',
        'namakategori',
    ];

    public $timestamps = false;

    // Relasi ke jenis_bantuan
    public function jenisBantuan()
    {
        return $this->belongsTo(JenisBantuan::class, 'idjenisbantuan', 'idjenisbantuan');
    }

    // Relasi ke usulan
    public function usulan()
    {
        return $this->hasMany(Usulan::class, 'idkategori', 'idkategori');
    }
}
