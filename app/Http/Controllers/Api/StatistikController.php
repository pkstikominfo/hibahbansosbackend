<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatistikController extends Controller
{
    public function getStatistik(Request $request)
    {
        try {

            /* ==========================
             * VALIDASI TAHUN
             * ========================== */
            $tahun = (int) $request->input('tahun');
            if ($tahun <= 0) {
                $tahun = now()->year;
            }

            /* ==========================
             * TOTAL GLOBAL
             * ========================== */
            $total = DB::table('usulan')
                ->where('tahun', $tahun)
                ->selectRaw('
                    COUNT(*) AS total_usulan,
                    COALESCE(SUM(anggaran_usulan),0) AS total_anggaran_usulan,
                    SUM(CASE WHEN status="disetujui" THEN 1 ELSE 0 END) AS total_disetujui,
                    COALESCE(SUM(CASE WHEN status="disetujui" THEN anggaran_disetujui END),0) AS total_anggaran_disetujui
                ')
                ->first();

            $spjTotal = DB::table('spj')
                ->join('usulan','usulan.idusulan','=','spj.idusulan')
                ->where('usulan.tahun',$tahun)
                ->selectRaw('
                    COUNT(spj.idspj) AS total_spj,
                    COALESCE(SUM(spj.realisasi),0) AS total_realisasi
                ')
                ->first();

            /* ==========================
             * GROUP BY KECAMATAN (SEMUA)
             * ========================== */
            $kecamatan = DB::table('kecamatan')
                ->leftJoin('desa','desa.idkecamatan','=','kecamatan.idkecamatan')
                ->leftJoin('usulan', function ($join) use ($tahun) {
                    $join->on('usulan.iddesa','=','desa.iddesa')
                         ->where('usulan.tahun','=',$tahun);
                })
                ->leftJoin('spj','spj.idusulan','=','usulan.idusulan')
                ->select(
                    'kecamatan.idkecamatan',
                    DB::raw('COUNT(DISTINCT usulan.idusulan) AS total_usulan'),
                    DB::raw('COALESCE(SUM(usulan.anggaran_usulan),0) AS total_anggaran_usulan'),
                    DB::raw('SUM(CASE WHEN usulan.status="disetujui" THEN 1 ELSE 0 END) AS total_disetujui'),
                    DB::raw('COALESCE(SUM(CASE WHEN usulan.status="disetujui" THEN usulan.anggaran_disetujui END),0) AS total_anggaran_disetujui'),
                    DB::raw('COUNT(DISTINCT spj.idspj) AS total_spj'),
                    DB::raw('COALESCE(SUM(spj.realisasi),0) AS total_realisasi')
                )
                ->groupBy('kecamatan.idkecamatan')
                ->get()
                ->map(fn($r) => [
                    'id_kecamatan' => $r->idkecamatan,
                    'total' => [
                        'usulan' => [
                            'total_usulan' => (int)$r->total_usulan,
                            'total_anggaran_usulan' => (int)$r->total_anggaran_usulan,
                        ],
                        'disetujui' => [
                            'total_disetujui' => (int)$r->total_disetujui,
                            'total_anggaran_disetujui' => (int)$r->total_anggaran_disetujui,
                        ],
                        'spj' => [
                            'total_spj' => (int)$r->total_spj,
                            'total_realisasi' => (int)$r->total_realisasi,
                        ],
                    ],
                ]);

            /* ==========================
             * GROUP BY DESA (SEMUA)
             * ========================== */
            $desa = DB::table('desa')
                ->leftJoin('usulan', function ($join) use ($tahun) {
                    $join->on('usulan.iddesa','=','desa.iddesa')
                         ->where('usulan.tahun','=',$tahun);
                })
                ->leftJoin('spj','spj.idusulan','=','usulan.idusulan')
                ->select(
                    'desa.iddesa',
                    DB::raw('COUNT(DISTINCT usulan.idusulan) AS total_usulan'),
                    DB::raw('COALESCE(SUM(usulan.anggaran_usulan),0) AS total_anggaran_usulan'),
                    DB::raw('SUM(CASE WHEN usulan.status="disetujui" THEN 1 ELSE 0 END) AS total_disetujui'),
                    DB::raw('COALESCE(SUM(CASE WHEN usulan.status="disetujui" THEN usulan.anggaran_disetujui END),0) AS total_anggaran_disetujui'),
                    DB::raw('COUNT(DISTINCT spj.idspj) AS total_spj'),
                    DB::raw('COALESCE(SUM(spj.realisasi),0) AS total_realisasi')
                )
                ->groupBy('desa.iddesa')
                ->get()
                ->map(fn($r) => [
                    'id_desa' => $r->iddesa,
                    'total' => [
                        'usulan' => [
                            'total_usulan' => (int)$r->total_usulan,
                            'total_anggaran_usulan' => (int)$r->total_anggaran_usulan,
                        ],
                        'disetujui' => [
                            'total_disetujui' => (int)$r->total_disetujui,
                            'total_anggaran_disetujui' => (int)$r->total_anggaran_disetujui,
                        ],
                        'spj' => [
                            'total_spj' => (int)$r->total_spj,
                            'total_realisasi' => (int)$r->total_realisasi,
                        ],
                    ],
                ]);

            /* ==========================
             * RESPONSE
             * ========================== */
            return response()->json([
                'success' => true,
                'message' => 'Data statistik berhasil diambil',
                'tahun' => $tahun,
                'total' => [
                    'usulan' => [
                        'total_usulan' => (int)$total->total_usulan,
                        'total_anggaran_usulan' => (int)$total->total_anggaran_usulan,
                    ],
                    'disetujui' => [
                        'total_disetujui' => (int)$total->total_disetujui,
                        'total_anggaran_disetujui' => (int)$total->total_anggaran_disetujui,
                    ],
                    'spj' => [
                        'total_spj' => (int)$spjTotal->total_spj,
                        'total_realisasi' => (int)$spjTotal->total_realisasi,
                    ],
                ],
                'kecamatan' => $kecamatan,
                'desa' => $desa,
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
