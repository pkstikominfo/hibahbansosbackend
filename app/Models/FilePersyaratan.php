<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilePersyaratan extends Model
{
    use HasFactory;

    protected $table = 'file_persyaratan';

    protected $primaryKey = 'id_fp';

    public $timestamps = false;

    protected $fillable = [
        'id_opd',
        'nama_persyaratan',
        'idsubjenisbantuan',
    ];

    /**
     * Relasi ke tabel opd
     */
    public function opd()
    {
        return $this->belongsTo(Opd::class, 'id_opd', 'kode_opd');
    }

    /**
     * Relasi ke sub_jenis_bantuan
     */
    public function subJenisBantuan()
    {
        return $this->belongsTo(SubJenisBantuan::class, 'idsubjenisbantuan', 'idsubjenisbantuan');
    }
}
