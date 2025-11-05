<?php
// app/Models/Spj.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spj extends Model
{
    use HasFactory;

    protected $table = 'spj';
    protected $primaryKey = 'idspj';


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

    public function getStatistikQB(  $where = [],  $betweenColumn = null, $betweenStart = null, $betweenEnd = null)
    {
        $tanggal_obj = new \DateTime("now");
        $awal_tahun = $tanggal_obj->format("Y") . "-01-01 00:00:00";
        $tanggal_berjalan = $tanggal_obj->format("Y-m-d H:i:s");

         // Tentukan kolom dan range between (bisa dikosongkan)
        $kolomBetween = $betweenColumn ?? 'created_at';
        $awalBetween = $betweenStart ?? $awal_tahun;
        $akhirBetween = $betweenEnd ?? $tanggal_berjalan;

        $query = $this::whereBetween($kolomBetween, [$awalBetween, $akhirBetween]);

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $operator = strtolower($value[0]);
                    $val = $value[1];

                    // Jika operator adalah IN atau NOT IN
                    if ($operator === 'in') {
                        $query->whereIn($key, $val);
                    } elseif ($operator === 'not in') {
                        $query->whereNotIn($key, $val);
                    } else {
                        // fallback ke where biasa
                        $query->where($key, $value[0], $value[1]);
                    }
                } else {
                    $query->where($key, $value);
                }
            }
        }

        return $query;
    }

    public function getStatistikJumlahPenerima(
        $where = [],
        $groupBy = null,
        $betweenColumn = null,
        $betweenStart = null,
        $betweenEnd = null
    ) {
        $query = $this->getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);

        if ($groupBy) $query->groupBy($groupBy);

        return $query->count();
    }

    public function getStatistikJumlahAnggaran(
    $where = [],
    $groupBy = null,
    $betweenColumn = 'tgl_verifikasi', // default SPJ pakai tgl_verifikasi
    $betweenStart = null,
    $betweenEnd = null
) {
    $query = $this->getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);

    // bangun select tanpa koma menggantung
    $selects = [
        'COALESCE(SUM(realisasi), 0) AS total_realisasi',
        // kalau kamu punya kolom anggaran_spj dan ingin disum juga, aktifkan baris di bawah:
        // 'COALESCE(SUM(anggaran_spj), 0) AS total_anggaran_spj',
    ];

    // jika group by, kolomnya harus ikut di-select
    if ($groupBy) {
        $query->addSelect($groupBy);
    }

    $query->selectRaw(implode(', ', $selects));

    if ($groupBy) {
        $query->groupBy($groupBy);
    }

    return $query->get();
}
}
