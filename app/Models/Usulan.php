<?php
// app/Models/Usulan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usulan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'usulan';
    protected $primaryKey = 'idusulan';

    protected $fillable = [
        'judul',
        'anggaran_usulan',
        'file_persyaratan',
        'email',
        'nohp',
        'idsubjenisbantuan',
        'idkategori',
        'anggaran_disetujui',
        'kode_opd',
        'status',
        'iddesa',
        'nama'
    ];

    public $timestamps = true;

    // Relasi ke sub_jenis_bantuan
    public function subJenisBantuan()
    {
        return $this->belongsTo(SubJenisBantuan::class, 'idsubjenisbantuan', 'idsubjenisbantuan');
    }

    // Relasi ke kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'idkategori', 'idkategori');
    }

    // Relasi ke OPD
    public function opd()
    {
        return $this->belongsTo(Opd::class, 'kode_opd', 'kode_opd');
    }

    // Relasi ke desa
    public function desa()
    {
        return $this->belongsTo(Desa::class, 'iddesa', 'iddesa');
    }

    // Relasi ke SPJ
    public function spj()
    {
        return $this->hasOne(Spj::class, 'idusulan', 'idusulan');
    }

    // Relasi ke usulan_log
    public function usulanLogs()
    {
        return $this->hasMany(UsulanLog::class, 'idusulan', 'idusulan');
    }

    // Scope untuk status
    public function scopeDiusulkan($query)
    {
        return $query->where('status', 'diusulkan');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', 'disetujui');
    }

    // Accessor untuk anggaran
    public function getAnggaranUsulanFormattedAttribute()
    {
        return 'Rp ' . number_format($this->anggaran_usulan, 0, ',', '.');
    }

    public function getAnggaranDisetujuiFormattedAttribute()
    {
        return $this->anggaran_disetujui ? 'Rp ' . number_format($this->anggaran_disetujui, 0, ',', '.') : '-';
    }
}
