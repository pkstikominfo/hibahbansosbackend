<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka)
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}
if (!function_exists('log_usulan')) {
    function log_usulan($data)
    {
        $id_user = Auth::check() ? Auth::user()->iduser : null;
        $data_insert = [
            'idusulan' => $data['idusulan'],
            'iduser' => $id_user,
            'tanggal' => now(),
        ];
        \App\Models\UsulanLog::create($data_insert);
    }
}
