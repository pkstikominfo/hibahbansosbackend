<?php
// app/Models/Usulan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


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
        'nama',
        'no_sk',
        'nama_lembaga',
        'catatan_ditolak',
        'tahun',
    ];

    protected array $statSumColumns = [
        'anggaran_disetujui' => 'total_anggaran_disetujui',
        'anggaran_usulan'    => 'total_anggaran_usulan',
    ];

    protected ?string $statDefaultBetweenColumn = 'created_at';

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
    public function bantuanLogs()
    {
        return $this->hasMany(BantuanLog::class, 'id_fk', 'idusulan');
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

    protected function prefixCol(string $col): string
{
    // kalau sudah ada ".", biarkan; kalau belum, prefix nama tabel model
    $tbl = $this->getTable(); // 'usulan'
    return str_contains($col, '.') ? $col : "{$tbl}.{$col}";
}

public function getStatistikQB($where = [], $betweenColumn = null, $betweenStart = null, $betweenEnd = null)
{
    $tanggal_obj = new \DateTime("now");
    $awal_tahun = $tanggal_obj->format("Y") . "-01-01 00:00:00";
    $tanggal_berjalan = $tanggal_obj->format("Y-m-d H:i:s");

    // between + prefix kolom agar tidak ambiguous setelah join
    $kolomBetween = $this->prefixCol($betweenColumn ?? 'created_at');
    $awalBetween  = $betweenStart ?? $awal_tahun;
    $akhirBetween = $betweenEnd   ?? $tanggal_berjalan;

    $qb = $this->newQuery()->whereBetween($kolomBetween, [$awalBetween, $akhirBetween]);

    // where dinamis + prefix kolom
    if (!empty($where)) {
        foreach ($where as $key => $value) {
            $key = $this->prefixCol($key);

            if (is_array($value)) {
                $operator = strtolower($value[0]);
                $val = $value[1];

                if ($operator === 'in')        $qb->whereIn($key, $val);
                elseif ($operator === 'not in') $qb->whereNotIn($key, $val);
                else                            $qb->where($key, $value[0], $value[1]);
            } else {
                $qb->where($key, $value);
            }
        }
    }

    // soft delete safety (kalau pakai SoftDeletes)
    if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive($this))) {
        $qb->whereNull($this->getTable() . '.deleted_at');
    }

    return $qb;
}

public function getStatistikJumlahPenerima($where = [], $groupBy = null, $betweenColumn = null, $betweenStart = null, $betweenEnd = null)
{
    $qb = $this->getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);

    if (!$groupBy) {
        return (int) $qb->count();
    }

    $groups = (array) $groupBy;
    $pref   = array_map(fn($g) => $this->prefixCol($g), $groups);

    // join desa kalau grouping pakai iddesa
    if (in_array('usulan.iddesa', $pref, true)) {
        $qb->leftJoin('desa', 'usulan.iddesa', '=', 'desa.iddesa');
        $qb->addSelect('desa.namadesa');
        $pref[] = 'desa.namadesa';
    }

    $qb->select(array_merge($pref, [DB::raw('COUNT(*) AS total_usulan')]))
       ->groupBy($pref);

    return $qb->get();
}

public function getStatistikJumlahAnggaran($where = [], $groupBy = null, $betweenColumn = null, $betweenStart = null, $betweenEnd = null)
{
    $qb = $this->getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);

    if (!$groupBy) {
        // satu baris total
        return $qb->selectRaw('
            COALESCE(SUM(anggaran_usulan), 0)    AS total_anggaran_usulan,
            COALESCE(SUM(anggaran_disetujui), 0) AS total_anggaran_disetujui
        ')->first();
    }

    $groups = (array) $groupBy;
    $pref   = array_map(fn($g) => $this->prefixCol($g), $groups);

    if (in_array('usulan.iddesa', $pref, true)) {
        $qb->leftJoin('desa', 'usulan.iddesa', '=', 'desa.iddesa');
        $qb->addSelect('desa.namadesa');
        $pref[] = 'desa.namadesa';
    }

    $qb->select(array_merge($pref, [
        DB::raw('COALESCE(SUM(anggaran_usulan), 0)    AS total_anggaran_usulan'),
        DB::raw('COALESCE(SUM(anggaran_disetujui), 0) AS total_anggaran_disetujui'),
    ]))->groupBy($pref);

    return $qb->get();
}


}
