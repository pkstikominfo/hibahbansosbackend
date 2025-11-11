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

    protected function prefixCol(string $col): string
{
    $tbl = $this->getTable(); // 'spj'
    return str_contains($col, '.') ? $col : "{$tbl}.{$col}";
}

public function getStatistikQB($where = [], $betweenColumn = 'tgl_verifikasi', $betweenStart = null, $betweenEnd = null)
{
    $tanggal_obj = new \DateTime("now");
    $awal_tahun = $tanggal_obj->format("Y") . "-01-01 00:00:00";
    $tanggal_berjalan = $tanggal_obj->format("Y-m-d H:i:s");

    $kolomBetween = $this->prefixCol($betweenColumn ?? 'tgl_verifikasi');
    $awalBetween  = $betweenStart ?? $awal_tahun;
    $akhirBetween = $betweenEnd   ?? $tanggal_berjalan;

    $qb = $this->newQuery()->whereBetween($kolomBetween, [$awalBetween, $akhirBetween]);

    if (!empty($where)) {
        foreach ($where as $key => $value) {
            $key = $this->prefixCol($key);
            if (is_array($value)) {
                $op  = strtolower($value[0]); $val = $value[1];
                if ($op === 'in')        $qb->whereIn($key, $val);
                elseif ($op === 'not in') $qb->whereNotIn($key, $val);
                else                      $qb->where($key, $value[0], $value[1]);
            } else {
                $qb->where($key, $value);
            }
        }
    }

    if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive($this))) {
        $qb->whereNull($this->getTable() . '.deleted_at');
    }

    return $qb;
}

public function getStatistikJumlahPenerima($where = [], $groupBy = null, $betweenColumn = 'tgl_verifikasi', $betweenStart = null, $betweenEnd = null)
{
    $qb = $this->getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);

    if (!$groupBy) {
        return (int) $qb->count();
    }

    $groups = (array) $groupBy;
    $pref   = array_map(fn($g) => $this->prefixCol($g), $groups);

    if (in_array('spj.iddesa', $pref, true)) {
        $qb->leftJoin('desa', 'spj.iddesa', '=', 'desa.iddesa');
        $qb->addSelect('desa.namadesa');
        $pref[] = 'desa.namadesa';
    }

    $qb->select(array_merge($pref, [DB::raw('COUNT(*) AS total_spj')]))
       ->groupBy($pref);

    return $qb->get();
}

public function getStatistikJumlahAnggaran($where = [], $groupBy = null, $betweenColumn = 'tgl_verifikasi', $betweenStart = null, $betweenEnd = null)
{
    $qb = $this->getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);

    if (!$groupBy) {
        return $qb->selectRaw('COALESCE(SUM(realisasi), 0) AS total_realisasi')->first();
    }

    $groups = (array) $groupBy;
    $pref   = array_map(fn($g) => $this->prefixCol($g), $groups);

    if (in_array('spj.iddesa', $pref, true)) {
        $qb->leftJoin('desa', 'spj.iddesa', '=', 'desa.iddesa');
        $qb->addSelect('desa.namadesa');
        $pref[] = 'desa.namadesa';
    }

    $qb->select(array_merge($pref, [
        DB::raw('COALESCE(SUM(realisasi), 0) AS total_realisasi'),
    ]))->groupBy($pref);

    return $qb->get();
}

public function bantuanLogs()
    {
        return $this->hasMany(BantuanLog::class, 'id_fk', 'idspj');
    }
}
