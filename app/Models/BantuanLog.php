<?php
// app/Models/UsulanLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BantuanLog extends Model
{
    use HasFactory;

    protected $table = 'bantuan_log';
    protected $primaryKey = 'idlog';

    protected $fillable = [
        'id_fk',
        'iduser',
        'tanggal',
        'tipe',
    ];

    public $timestamps = false;

    // Relasi ke usulan
    public function usulan()
    {
        return $this->belongsTo(Usulan::class, 'id_fk', 'idusulan');
    }

    public function spj()
    {
        return $this->belongsTo(Spj::class, 'id_fk', 'idspj');
    }

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'iduser', 'id');
    }

    // Cast tanggal
    protected $casts = [
        'tanggal' => 'datetime',
    ];
}
