<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desa extends Model
{
    use HasFactory;

    protected $table = 'desa';
    protected $primaryKey = 'iddesa';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'idkecamatan',
        'namadesa',
        'latitude',
        'longitude'
    ];

    protected $hidden = [];

    /**
     * Relasi many-to-one dengan Kecamatan.
     */
    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'idkecamatan', 'idkecamatan');
    }

    /**
     * Scope query untuk mencari desa berdasarkan nama.
     */
    public function scopeCariNama($query, $nama)
    {
        return $query->where('namadesa', 'like', '%' . $nama . '%');
    }

    /**
     * Scope query untuk mencari desa berdasarkan kecamatan.
     */
    public function scopeDariKecamatan($query, $idKecamatan)
    {
        return $query->where('idkecamatan', $idKecamatan);
    }

    /**
     * Scope query untuk mencari desa berdasarkan koordinat (radius dalam km)
     */
    public function scopeDekatDengan($query, $latitude, $longitude, $radiusKm = 10)
    {
        $earthRadius = 6371; // Radius bumi dalam kilometer

        return $query->selectRaw("
            *,
            ($earthRadius * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))) AS distance
        ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    /**
     * Accessor untuk nama desa (title case).
     */
    public function getNamadesaAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    /**
     * Mutator untuk nama desa (menyimpan sebagai lowercase).
     */
    public function setNamadesaAttribute($value)
    {
        $this->attributes['namadesa'] = strtolower($value);
    }

    /**
     * Accessor untuk koordinat dalam format array.
     */
    public function getKoordinatAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude
            ];
        }
        return null;
    }

    /**
     * Accessor untuk nama desa lengkap (dengan nama kecamatan).
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
    /**
     * Check apakah desa memiliki koordinat.
     */
    public function memilikiKoordinat()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }
}
