<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Usulan;
use Illuminate\Auth\Access\Response;

class UsulanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua user yang login bisa lihat usulan (dengan filter berbeda)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Usulan $usulan): bool
    {
        // Admin bisa lihat semua
        if ($user->isAdmin()) {
            return true;
        }

        // OPD bisa lihat usulan unassigned atau usulan OPD mereka
        if ($user->isOpd()) {
            return is_null($usulan->kode_opd) || $usulan->kode_opd === $user->kode_opd;
        }

        // Pengusul hanya bisa lihat usulan mereka sendiri
        if ($user->isPengusul()) {
            return $usulan->email === $user->email; // Asumsi email sebagai identifier
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Hanya pengusul yang bisa buat usulan baru
        return $user->isPengusul();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Usulan $usulan): bool
    {
        // Admin bisa update semua
        if ($user->isAdmin()) {
            return true;
        }

        // Pengusul hanya bisa update usulan mereka yang belum di-assign
        if ($user->isPengusul()) {
            return $usulan->email === $user->email && $usulan->canBeEditedByPengusul();
        }

        // OPD hanya bisa update usulan yang sudah di-assign ke mereka
        if ($user->isOpd()) {
            return $usulan->kode_opd === $user->kode_opd;
        }

        return false;
    }

    /**
     * Determine whether the user can assign OPD to usulan.
     */
    public function assignOpd(User $user, Usulan $usulan): bool
    {
        // Admin bisa assign ke OPD mana pun
        if ($user->isAdmin()) {
            return $usulan->canBeAssignedByOpd();
        }

        // OPD hanya bisa assign ke OPD mereka sendiri
        if ($user->isOpd()) {
            return $usulan->canBeAssignedByOpd();
        }

        return false;
    }

    /**
     * Determine whether the user can approve usulan.
     */
    public function approve(User $user, Usulan $usulan): bool
    {
        // Admin bisa approve semua
        if ($user->isAdmin()) {
            return true;
        }

        // OPD hanya bisa approve usulan OPD mereka
        if ($user->isOpd()) {
            return $usulan->kode_opd === $user->kode_opd;
        }

        return false;
    }
}
