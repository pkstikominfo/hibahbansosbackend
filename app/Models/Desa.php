<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desa extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'desa';

    /**
     * Primary key untuk model.
     *
     * @var string
     */
    protected $primaryKey = 'iddesa';

    /**
     * Tipe data primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Menunjukkan apakah primary key auto increment.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Nonaktifkan timestamps (created_at dan updated_at)
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array
     */
    protected $fillable = [
        'iddesa',
        'idkecamatan',
        'namadesa'
    ];

    /**
     * Kolom yang harus disembunyikan dari array dan JSON.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Relasi many-to-one dengan Kecamatan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'idkecamatan', 'idkecamatan');
    }

    /**
     * Scope query untuk mencari desa berdasarkan nama.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $nama
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCariNama($query, $nama)
    {
        return $query->where('namadesa', 'like', '%' . $nama . '%');
    }

    /**
     * Scope query untuk mencari desa berdasarkan kecamatan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $idKecamatan
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDariKecamatan($query, $idKecamatan)
    {
        return $query->where('idkecamatan', $idKecamatan);
    }

    /**
     * Accessor untuk nama desa (title case).
     *
     * @param string $value
     * @return string
     */
    public function getNamadesaAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    /**
     * Mutator untuk nama desa (menyimpan sebagai lowercase).
     *
     * @param string $value
     * @return void
     */
    public function setNamadesaAttribute($value)
    {
        $this->attributes['namadesa'] = strtolower($value);
    }

    /**
     * Accessor untuk nama desa lengkap (dengan nama kecamatan).
     *
     * @return string
     */
    public function getNamaLengkapAttribute()
    {
        return $this->namadesa . ', ' . ($this->kecamatan ? $this->kecamatan->namakecamatan : 'N/A');
    }


    // Relasi ke usulan_log
    public function usulan()
    {
        return $this->hasMany(Usulan::class, 'iddesa', 'iddesa');
    }
}
