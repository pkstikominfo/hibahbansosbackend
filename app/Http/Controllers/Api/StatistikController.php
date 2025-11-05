<?php

namespace App\Http\Controllers\Api;
use App\Models\Usulan;
use App\Models\Spj;
use Illuminate\Http\Request;

class StatistikController
{
    public function getStatistik(Request $request)
{
    // ---- helper kecil buat bikin array where sederhana (mendukung IN)
    $makeWhere = function (?string $col, $val): array {
        if (empty($col) || $val === null || $val === '') return [];
        return is_array($val) ? [$col => ['in', $val]] : [$col => $val];
    };

    // ========= USULAN =========
    $usulan_where       = $makeWhere($request->input('usulan_where_column'), $request->input('usulan_where_value'));
    $usulan_group_by    = $request->input('usulan_group_by');              // ex: iddesa
    // default between Usulan: created_at (biarkan null => fallback di model)
    $usulan_between_col   = $request->input('usulan_between_column');      // default null -> created_at
    $usulan_between_start = $request->input('usulan_between_start');       // optional
    $usulan_between_end   = $request->input('usulan_between_end');         // optional

    // ========= SPJ =========
    $spj_where         = $makeWhere($request->input('spj_where_column'), $request->input('spj_where_value'));
    $spj_group_by      = $request->input('spj_group_by');                  // ex: idkecamatan
    // default between SPJ: tgl_verifikasi (kalau tidak dikirim, kita set sendiri)
    $spj_between_col   = $request->input('spj_between_column', 'created_at');
    $spj_between_start = $request->input('spj_between_start');             // optional
    $spj_between_end   = $request->input('spj_between_end');               // optional

    // ========= eksekusi per-model =========
    $jumlahUsulan = (new Usulan)->getStatistikJumlahPenerima(
        $usulan_where,
        $usulan_group_by,
        $usulan_between_col,
        $usulan_between_start,
        $usulan_between_end
    );

    $jumlahAnggaranUsulan = (new Usulan)->getStatistikJumlahAnggaran(
        $usulan_where,
        $usulan_group_by,
        $usulan_between_col,
        $usulan_between_start,
        $usulan_between_end
    );

    $jumlahSpj = (new Spj)->getStatistikJumlahPenerima(
        $spj_where,
        $spj_group_by,
        $spj_between_col,
        $spj_between_start,
        $spj_between_end
    );

    $jumlahAnggaranSpj = (new Spj)->getStatistikJumlahAnggaran(
        $spj_where,
        $spj_group_by,
        $spj_between_col,
        $spj_between_start,
        $spj_between_end
    );

    return response()->json([
        'success'                => true,
        'message'                => 'Data statistik berhasil diambil',
        'usulan'                 => [
        'total_usulan'           => $jumlahUsulan,
        'total_anggaran_usulan'  => $jumlahAnggaranUsulan,
         ],
         'spj'                   => [
            'total_spj'              => $jumlahSpj,
            'total_anggaran_spj'     => $jumlahAnggaranSpj,
        ],
    ]);
}

}
