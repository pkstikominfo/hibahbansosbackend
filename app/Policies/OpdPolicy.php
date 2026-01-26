<?php

namespace App\Policies;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OpdPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Opd $opd): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    // public function update(User $user, Opd $opd): bool
    // {
    //     // 1. Admin BOLEH edit semua OPD
    //     if ($user->peran === 'admin') {
    //         return true;
    //     }

    //     // 2. User OPD HANYA BOLEH edit jika kode_opd user == kode_opd data yang diedit
    //     if ($user->peran === 'opd') {
    //         // Pastikan user punya kode_opd & cocok dengan data target
    //         return $user->kode_opd === $opd->kode_opd;
    //     }

    //     // 3. Pengusul atau role lain TIDAK BOLEH
    //     return false;
    // }

    public function update(User $user, Opd $opd): bool
    {
        // 1. Admin BOLEH edit semua OPD
        if ($user->peran === 'admin') {
            return true;
        }

        // 2. User OPD
        if ($user->peran === 'opd') { // Pastikan 'opd' ini sesuai database (huruf kecil/besar)

            // --- DEBUGGING ---
            // Buka file storage/logs/laravel.log setelah menjalankan request ini
            \Illuminate\Support\Facades\Log::info("DEBUG POLICY:");
            \Illuminate\Support\Facades\Log::info("User Role: " . $user->peran);
            \Illuminate\Support\Facades\Log::info("User Kode: " . $user->kode_opd);
            \Illuminate\Support\Facades\Log::info("Target Data Kode: " . $opd->kode_opd);
            // -----------------

            return $user->kode_opd === $opd->kode_opd;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Opd $opd): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Opd $opd): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Opd $opd): bool
    {
        return false;
    }
}
