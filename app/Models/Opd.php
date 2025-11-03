<?php
// app/Models/Opd.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    use HasFactory;

    protected $table = 'opd';
    protected $primaryKey = 'kode_opd';

    protected $fillable = [
        'kode_opd',
        'nama_opd',
    ];

    public $timestamps = false;

    // Relasi ke users
    public function users()
    {
        return $this->hasMany(User::class, 'kode_opd', 'kode_opd');
    }

    // Relasi ke usulan
    public function usulan()
    {
        return $this->hasMany(Usulan::class, 'kode_opd', 'kode_opd');
    }
}
