<?php
// app/Models/UsulanLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsulanLog extends Model
{
    use HasFactory;

    protected $table = 'usulan_log';
    protected $primaryKey = 'idlog';

    protected $fillable = [
        'idusulan',
        'iduser',
        'tanggal',
    ];

    public $timestamps = false;

    // Relasi ke usulan
    public function usulan()
    {
        return $this->belongsTo(Usulan::class, 'idusulan', 'idusulan');
    }

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'iduser', 'iduser');
    }

    // Cast tanggal
    protected $casts = [
        'tanggal' => 'datetime',
    ];
}
