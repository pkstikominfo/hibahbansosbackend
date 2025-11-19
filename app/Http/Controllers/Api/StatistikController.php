<?php

namespace App\Http\Controllers\Api;
use App\Models\Usulan;
use App\Models\Spj;
use Illuminate\Http\Request;

class StatistikController
{
    public function getStatistik(Request $request)
{
    // helper kecil buat bikin array where sederhana (mendukung IN)
    $makeWhere = function (?string $col, $val): array {
        if (empty($col) || $val === null || $val === '') return [];
        return is_array($val) ? [$col => ['in', $val]] : [$col => $val];
    };

    // 🔹 Tahun global: default tahun sekarang, bisa override ?tahun=2024
    $tahun = (int) $request->input('tahun', now()->year);

    // ========= USULAN =========
    $usulan_where    = $makeWhere(
        $request->input('usulan_where_column'),
        $request->input('usulan_where_value')
    );
    $usulan_group_by = $request->input('usulan_group_by'); // ex: iddesa

    // ========= SPJ =========
    $spj_where = $makeWhere(
        $request->input('spj_where_column'),
        $request->input('spj_where_value')
    );
    $spj_group_by = $request->input('spj_group_by'); // ex: idkecamatan

    // ========= eksekusi per-model =========
    // USULAN pakai kolom usulan.tahun
    $jumlahUsulan = (new Usulan)->getStatistikJumlahPenerima(
        $usulan_where,
        $usulan_group_by,
        $tahun
    );

    $jumlahAnggaranUsulan = (new Usulan)->getStatistikJumlahAnggaran(
        $usulan_where,
        $usulan_group_by,
        $tahun
    );

    // SPJ pakai usulan.tahun (lihat poin 3 di bawah untuk model Spj)
    $jumlahSpj = (new Spj)->getStatistikJumlahPenerima(
        $spj_where,
        $spj_group_by,
        $tahun
    );

    $jumlahAnggaranSpj = (new Spj)->getStatistikJumlahAnggaran(
        $spj_where,
        $spj_group_by,
        $tahun
    );

    return response()->json([
        'success' => true,
        'message' => 'Data statistik berhasil diambil',
        'usulan'  => [
            'total_usulan'          => $jumlahUsulan,
            'total_anggaran_usulan' => $jumlahAnggaranUsulan,
        ],
        'spj'     => [
            'total_spj'          => $jumlahSpj,
            'total_anggaran_spj' => $jumlahAnggaranSpj,
        ],
    ]);
}
}
