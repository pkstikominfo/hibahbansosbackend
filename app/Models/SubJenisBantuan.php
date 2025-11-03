<?php
// app/Models/SubJenisBantuan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubJenisBantuan extends Model
{
    use HasFactory;

    protected $table = 'sub_jenis_bantuan';
    protected $primaryKey = 'idsubjenisbantuan';

    protected $fillable = [
        'idjenisbantuan',
        'namasubjenis',
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
        return $this->hasMany(Usulan::class, 'idsubjenisbantuan', 'idsubjenisbantuan');
    }
}
