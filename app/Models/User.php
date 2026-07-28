<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $fillable = [
        'username',
        'password',
        'name',
        'email',
        'nohp',
        'peran',
        'kode_opd',
        'status',
        'sso_sub',
        'sso_username',
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
        return $this->hasMany(UsulanLog::class, 'iduser', 'id');
    }

    // Relasi ke SPJ
    public function spjCreated()
    {
        return $this->hasMany(Spj::class, 'created_by', 'id');
    }

    public function spjUpdated()
    {
        return $this->hasMany(Spj::class, 'updated_by', 'id');
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

    public function validateCredentials($password)
    {
        return Hash::check($password, $this->password);
    }

    public function scopeSso($query)
    {
        return $query->whereNotNull('sso_sub');
    }

    public static function syncFromSsoToken(array $payload)
    {
        $sub = $payload['sub'] ?? null;
        if (!$sub) {
            return null;
        }

        // Cari berdasarkan username dari sso payload, biasanya 'preferred_username'
        $ssoUsername = $payload['preferred_username'] ?? null;
        
        // Cari user lokal berdasarkan username (NIP)
        $user = self::where('username', $ssoUsername)
            ->orWhere('sso_sub', $sub)
            ->first();

        // Cari berdasarkan email jika belum ketemu (opsional untuk migrasi)
        if (!$user && isset($payload['email'])) {
            $user = self::where('email', $payload['email'])->first();
        }

        // Jika user tidak terdaftar di sistem lokal, TOLAK AKSES
        if (!$user) {
            return null; 
        }

        // Jika terdaftar, update profilnya saja dari SSO
        $user->sso_sub = $sub;
        $user->sso_username = $ssoUsername;
        $user->name = $payload['name'] ?? ($ssoUsername ?? $user->name);
        if (isset($payload['email'])) {
            $user->email = $payload['email'];
        }
        
        $user->save();

        return $user;
    }

    public function hasKeycloakRole(array $ssoUser, string $role): bool
    {
        $roles = $ssoUser['realm_access']['roles'] ?? [];
        return in_array($role, $roles);
    }
}
