<?php
// app/Models/Spj.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStatistik;

class Spj extends Model
{
    use HasFactory;
    use HasStatistik;

    protected $table = 'spj';
    protected $primaryKey = 'idspj';

    protected array $statSumColumns = [
        // sesuaikan dengan kolom di tabel SPJ-mu
        'anggaran_disetujui' => 'total_anggaran_disetujui',
        'anggaran_usulan'    => 'total_anggaran_usulan',
        // atau misal:
        // 'nilai_spj_disetujui' => 'total_spj_disetujui',
        // 'nilai_spj_usulan'    => 'total_spj_usulan',
    ];

    protected ?string $statDefaultBetweenColumn = 'created_at';

    protected $fillable = [
        'idusulan',
        'file_pertanggungjawaban',
        'foto',
        'realisasi',
        'status',
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
    public function scopeDiusulkan($query)
    {
        return $query->where('status', 'diusulkan');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', 'disetujui');
    }

     public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
