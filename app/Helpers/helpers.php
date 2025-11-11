<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka)
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}
if (!function_exists('log_bantuan')) {
    function log_bantuan($data)
    {
        $id_user = Auth::check() ? Auth::user()->iduser : null;
        $data_insert = [
            'id_fk' => $data['id_fk'],
            'iduser' => $id_user,
            'tanggal' => now(),
        ];
        \App\Models\BantuanLog::create($data_insert);
    }
}
