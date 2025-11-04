<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'opd';

    /**
     * Primary key untuk model.
     *
     * @var string
     */
    protected $primaryKey = 'kode_opd';

    /**
     * Tipe data primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Menunjukkan apakah primary key auto increment.
     *
     * @var bool
     */
    public $incrementing = false;

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
        'kode_opd',
        'nama_opd'
    ];

    /**
     * Kolom yang harus disembunyikan dari array dan JSON.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Relasi one-to-many dengan Users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'kode_opd', 'kode_opd');
    }

    /**
     * Relasi one-to-many dengan Usulan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usulans()
    {
        return $this->hasMany(Usulan::class, 'kode_opd', 'kode_opd');
    }

    /**
     * Scope query untuk mencari berdasarkan nama OPD.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $nama
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCariNama($query, $nama)
    {
        return $query->where('nama_opd', 'like', '%' . $nama . '%');
    }

    /**
     * Accessor untuk nama OPD (title case).
     *
     * @param string $value
     * @return string
     */
    public function getNamaOpdAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    /**
     * Mutator untuk nama OPD (menyimpan sebagai lowercase).
     *
     * @param string $value
     * @return void
     */
    public function setNamaOpdAttribute($value)
    {
        $this->attributes['nama_opd'] = strtolower($value);
    }
}
