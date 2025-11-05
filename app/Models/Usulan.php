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
        $betweenColumn = null,
        $betweenStart = null,
        $betweenEnd = null
    ) {
        $query = $this->getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd)
            ->selectRaw('
                SUM(anggaran_disetujui) as total_anggaran_disetujui,
                SUM(anggaran_usulan)    as total_anggaran_usulan
            ');

        if ($groupBy) $query->groupBy($groupBy);

        return $query->get();
    }


}
