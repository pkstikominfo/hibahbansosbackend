<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsulanPersyaratan extends Model
{
    use HasFactory;

    protected $table = 'usulan_persyaratan';

    protected $primaryKey = 'id_up';

    public $timestamps = false;

    protected $fillable = [
        'idusulan',
        'id_fp',
        'file_persyaratan',
    ];

    /**
     * Relasi ke tabel usulan
     */
    public function usulan()
    {
        return $this->belongsTo(Usulan::class, 'idusulan', 'idusulan');
    }

    /**
     * Relasi ke file_persyaratan
     */
    public function filePersyaratan()
    {
        return $this->belongsTo(FilePersyaratan::class, 'id_fp', 'id_fp');
    }
}
