<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'iduser';

    protected $fillable = [
        'username',
        'password',
        'nama',
        'email',
        'nohp',
        'peran',
        'kode_opd',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    // Relasi ke OPD
    public function opd()
    {
        return $this->belongsTo(Opd::class, 'kode_opd', 'kode_opd');
    }

    // Relasi ke usulan_log
    public function usulanLogs()
    {
        return $this->hasMany(UsulanLog::class, 'iduser', 'iduser');
    }

    // Scope untuk filter peran
    public function scopeAdmin($query)
    {
        return $query->where('peran', 'admin');
    }

    public function scopeOpd($query)
    {
        return $query->where('peran', 'opd');
    }

    public function scopePengusul($query)
    {
        return $query->where('peran', 'pengusul');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Method helper untuk pengecekan peran
    public function isAdmin()
    {
        return $this->peran === 'admin';
    }

    public function isOpd()
    {
        return $this->peran === 'opd';
    }

    public function isPengusul()
    {
        return $this->peran === 'pengusul';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}
