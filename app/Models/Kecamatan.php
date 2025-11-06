<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'kecamatan';

    /**
     * Primary key untuk model.
     *
     * @var string
     */
    protected $primaryKey = 'idkecamatan';

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
        'namakecamatan'
    ];

    /**
     * Kolom yang harus disembunyikan dari array dan JSON.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Relasi one-to-many dengan Desa.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function desas()
    {
        return $this->hasMany(Desa::class, 'idkecamatan', 'idkecamatan');
    }

    /**
     * Scope query untuk mencari berdasarkan nama kecamatan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $nama
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCariNama($query, $nama)
    {
        return $query->where('namakecamatan', 'like', '%' . $nama . '%');
    }

    /**
     * Accessor untuk nama kecamatan (title case).
     *
     * @param string $value
     * @return string
     */
    public function getNamakecamatanAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    /**
     * Mutator untuk nama kecamatan (menyimpan sebagai lowercase).
     *
     * @param string $value
     * @return void
     */
    public function setNamakecamatanAttribute($value)
    {
        $this->attributes['namakecamatan'] = strtolower($value);
    }

     public function usulan()
    {
        return $this->hasManyThrough(
            Usulan::class,    // model tujuan
            Desa::class,      // perantara
            'idkecamatan',    // FK di Desa yang refer ke Kecamatan
            'iddesa',         // FK di Usulan yang refer ke Desa
            'idkecamatan',    // PK Kecamatan
            'iddesa'          // PK Desa
        );
    }
}
