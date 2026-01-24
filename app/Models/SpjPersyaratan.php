<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpjPersyaratan extends Model
{
    use HasFactory;

    protected $table = 'spj_persyaratan';

    protected $primaryKey = 'id_sp';

    public $timestamps = false;

    protected $fillable = [
        'idspj',
        'file_persyaratan',
    ];

    /**
     * Relasi ke tabel spj
     */
    public function spj()
    {
        return $this->belongsTo(Spj::class, 'idspj', 'idspj');
    }
}
