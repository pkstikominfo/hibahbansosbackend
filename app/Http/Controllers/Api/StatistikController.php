<?php

namespace App\Http\Controllers\Api;
use App\Models\Usulan;
use App\Models\Spj;
use Illuminate\Http\Request;

class StatistikController
{
    public function getStatistik(Request $request)
    {
        $where_column   = $request->input('where_column');
        $where_value    = $request->input('where_value');
        $group_by       = $request->input('group_by');

        $between_column = $request->input('between_column'); // optional
        $between_start  = $request->input('between_start');  // optional
        $between_end    = $request->input('between_end');    // optional

        $where = [];
        if (!empty($where_column) && $where_value !== null && $where_value !== '') {
            $where[$where_column] = is_array($where_value)
                ? ['in', $where_value]
                : $where_value;
        }
        if (!empty($between_column) && $between_start !== null && $between_end !== null) {
            // Bisa juga pakai properti default di model, kalau between_column kosong
            // Di sini kita pakai request jika ada
        }

        $jumlahUsulan         = Usulan::getStatistikJumlahPenerima($where, $group_by, $between_column, $between_start, $between_end);
        $jumlahAnggaranUsulan = Usulan::getStatistikJumlahAnggaran($where, $group_by, $between_column, $between_start, $between_end);

        $jumlahSpj            = Spj::getStatistikJumlahPenerima($where, $group_by, $between_column, $between_start, $between_end);
        $jumlahAnggaranSpj    = Spj::getStatistikJumlahAnggaran($where, $group_by, $between_column, $between_start, $between_end);

        return response()->json([
            'success'               => true,
            'message'               => 'Data statistik berhasil diambil',
            'total_usulan'          => $jumlahUsulan,
            'total_anggaran_usulan' => $jumlahAnggaranUsulan,
            'total_spj'             => $jumlahSpj,
            'total_anggaran_spj'    => $jumlahAnggaranSpj,
        ]);
    }

}
