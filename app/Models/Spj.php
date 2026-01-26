<?php
// app/Models/Spj.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Spj extends Model
{
    use HasFactory;

    protected $table = 'spj';
    protected $primaryKey = 'idspj';


    protected $fillable = [
        'idusulan',
        'foto',
        'realisasi',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;

    // Relasi ke usulan
    public function usulan()
    {
        return $this->belongsTo(Usulan::class, 'idusulan', 'idusulan');
    }

    // Accessor untuk realisasi formatted
    public function getRealisasiFormattedAttribute()
    {
        return 'Rp ' . number_format($this->realisasi, 0, ',', '.');
    }

    // Scope untuk status

     public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }


public function bantuanLogs()
    {
        return $this->hasMany(BantuanLog::class, 'id_fk', 'idspj');
    }
}
