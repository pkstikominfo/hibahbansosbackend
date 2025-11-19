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

public function getStatistikQB($where = [], ?int $tahun = null)
{
    $tahun = $tahun ?? (int) date('Y');

    $qb = $this->newQuery()
        ->join('usulan', 'usulan.idusulan', '=', 'spj.idusulan')
        ->where('usulan.tahun', $tahun);

    // where dinamis
    if (!empty($where)) {
        foreach ($where as $key => $value) {
            $key = $this->prefixCol($key); // mis: "status" -> "spj.status"

            if (is_array($value)) {
                $operator = strtolower($value[0]);
                $val      = $value[1];

                if ($operator === 'in') {
                    $qb->whereIn($key, $val);
                } elseif ($operator === 'not in') {
                    $qb->whereNotIn($key, $val);
                } else {
                    $qb->where($key, $value[0], $value[1]);
                }
            } else {
                $qb->where($key, $value);
            }
        }
    }

    return $qb;
}

public function getStatistikJumlahPenerima($where = [], $groupBy = null, ?int $tahun = null)
{
    $qb = $this->getStatistikQB($where, $tahun);

    if (!$groupBy) {
        return (int) $qb->count();
    }

    $groups = (array) $groupBy;
    $pref   = array_map(fn($g) => $this->prefixCol($g), $groups);

    $qb->select(array_merge($pref, [DB::raw('COUNT(*) AS total_spj')]))
       ->groupBy($pref);

    return $qb->get();
}

public function getStatistikJumlahAnggaran($where = [], $groupBy = null, ?int $tahun = null)
{
    $qb = $this->getStatistikQB($where, $tahun);

    if (!$groupBy) {
        return $qb->selectRaw('
            COALESCE(SUM(realisasi), 0) AS total_realisasi
        ')->first();
    }

    $groups = (array) $groupBy;
    $pref   = array_map(fn($g) => $this->prefixCol($g), $groups);

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
