<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usulan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'usulan';
    protected $primaryKey = 'idusulan';
    public $timestamps = true;

    /**
     * =====================================================
     * FIELD YANG BOLEH MASS ASSIGNMENT (CREATE SAJA)
     * =====================================================
     * Field sensitif TIDAK DIMASUKKAN
     */
    protected $fillable = [
        // field lain yang memang boleh diupdate
        'judul',
        'anggaran_usulan',
        'anggaran_disetujui',
        'status',
        'catatan_ditolak',
        'tahun',
        'kode_opd',
    ];

    /**
     * =====================================================
     * BLOK PERUBAHAN FIELD SENSITIF (UPDATE)
     * =====================================================
     */
    protected static function booted()
    {
        static::updating(function ($usulan) {

            $forbiddenFields = [
                'iddesa',
                'email',
                'nohp',
                'idsubjenisbantuan',
                'idkategori',
                'nama',
            ];

            $dirty = array_keys($usulan->getDirty());

            foreach ($dirty as $field) {
                if (in_array($field, $forbiddenFields, true)) {
                    throw new \Exception(
                        "Field {$field} tidak boleh diubah"
                    );
                }
            }
        });
    }

    /* ===================== RELATIONS ===================== */

    public function subJenisBantuan()
    {
        return $this->belongsTo(
            SubJenisBantuan::class,
            'idsubjenisbantuan',
            'idsubjenisbantuan'
        );
    }

    public function kategori()
    {
        return $this->belongsTo(
            Kategori::class,
            'idkategori',
            'idkategori'
        );
    }

    public function desa()
    {
        return $this->belongsTo(
            Desa::class,
            'iddesa',
            'iddesa'
        );
    }
    public function opd()
    {
        return $this->belongsTo(
            Opd::class,
            'kode_opd',
            'kode_opd'
        );
    }
}
