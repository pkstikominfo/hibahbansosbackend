<?php

namespace App\Http\Controllers\Api;

use App\Models\Usulan;
use App\Models\Kecamatan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class UsulanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource with authorization filter
     */
    public function index(Request $request)
{
    try {

        $user = $request->user();

        $query = Usulan::query()
            ->leftJoin('kategori', 'usulan.idkategori', '=', 'kategori.idkategori')
            ->leftJoin('sub_jenis_bantuan', 'usulan.idsubjenisbantuan', '=', 'sub_jenis_bantuan.idsubjenisbantuan')
            ->with('usulanPersyaratan')
            ->select([
                'usulan.*',
                'kategori.namakategori as nama_kategori',
                'sub_jenis_bantuan.namasubjenis as nama_subjenis',
            ]);

        // 🔐 Role Filter
        if ($user->isPengusul()) {
            $query->where('usulan.email', $user->email);
        } elseif ($user->isOpd()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('usulan.kode_opd')
                  ->orWhere('usulan.kode_opd', $user->kode_opd);
            });
        }

        // 🔍 Search
        if ($search = $request->q) {
            $query->where(function ($q) use ($search) {
                $q->where('usulan.judul', 'like', "%$search%")
                  ->orWhere('kategori.namakategori', 'like', "%$search%")
                  ->orWhere('sub_jenis_bantuan.namasubjenis', 'like', "%$search%");
            });
        }

        // 🔽 Sorting
        $sortBy  = $request->input('sort_by', 'idusulan');
        $sortDir = $request->input('sort_dir', 'asc');

        $query->orderBy($sortBy, $sortDir);

        // 🔄 Pagination
        $usulan = $query->paginate(
            (int) $request->input('per_page', 10)
        );

        // 🔗 UBAH file_persyaratan → LINK
        $usulan->getCollection()->transform(function ($item) {
            $item->usulanPersyaratan->transform(function ($p) {
                $p->file_persyaratan = $p->file_persyaratan
                    ? asset('uploads/' . $p->file_persyaratan)
                    : null;
                return $p;
            });
            return $item;
        });

        return response()->json([
            'code' => 'success',
            'data' => $usulan->items(),
            'meta' => [
                'page' => $usulan->currentPage(),
                'total' => $usulan->total()
            ]
        ]);

    } catch (Throwable $e) {
        return response()->json([
            'code' => 'error',
            'message' => 'Gagal mengambil data',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function showByHash(string $hash)
{
    try {
        // =========================
        // DEKRIP ID USULAN
        // =========================
        $idusulan = (int) Crypt::decryptString(urldecode($hash));

        $usulan = Usulan::with([
            'subJenisBantuan',
            'kategori',
            'desa',
            'opd',
        ])->findOrFail($idusulan);

        return response()->json([
            'code'    => 'success',
            'message' => 'OK',
            'data'    => $usulan,
        ]);

    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        return response()->json([
            'code'    => 'error',
            'message' => 'Link tidak valid atau sudah rusak',
        ], 400);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'code'    => 'error',
            'message' => 'Data usulan tidak ditemukan',
        ], 404);

    } catch (\Throwable $e) {
        return response()->json([
            'code'    => 'error',
            'message' => 'Gagal mengambil data usulan',
            'error'   => $e->getMessage(),
        ], 500);
    }
}



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
            try {
                // =========================
                // VALIDASI REQUEST
                // =========================
                $validated = $request->validate([
                    'judul'              => ['required', 'string', 'max:255'],
                    'anggaran_usulan'    => ['required', 'integer', 'min:0'],
                    'email'              => ['required', 'email'],
                    'nohp'               => ['required', 'string', 'max:15'],
                    'nama'               => ['required', 'string', 'max:100'],
                    'status'             => ['required', 'in:diusulkan'],
                    'idsubjenisbantuan'  => ['required', 'integer'],
                    'idkategori'         => ['required', 'integer'],
                    'iddesa'             => ['required', 'integer'],
                    'kode_opd'           => ['required', 'string'],
                    'tahun'              => ['required', 'digits:4'],
                    'otp'                => ['required', 'digits:6'],
                ]);

                // =========================
                // VALIDASI OTP
                // =========================
                if (!validateOtp($validated['nohp'], $validated['otp'])) {
                    return response()->json([
                        'code' => 'error',
                        'message' => 'OTP tidak valid atau sudah kedaluwarsa'
                    ], 422);
                }

                unset($validated['otp']); // jangan simpan OTP

                // =========================
                // SIMPAN USULAN
                // =========================
                $usulan = Usulan::create($validated);
                log_bantuan(['id_fk' => $usulan->idusulan]);

                // =========================
                // KIRIM LINK VIA WHATSAPP
                // =========================
            $hash = urlencode(
                    Crypt::encryptString((string) $usulan->idusulan)
                );

                $link = url("/api/u/{$hash}");

                $pesan = "📄 *Usulan Anda Berhasil Diproses*\n\n"
                    . "Judul: {$usulan->judul}\n"
                    . "Tahun: {$usulan->tahun}\n\n"
                    . "🔗 *Lihat detail usulan Anda di sini:*\n"
                    . "{$link}\n\n"
                    . "Simpan pesan ini untuk referensi Anda.";

                send_whatsapp(getTokenFonte(), $usulan->nohp, $pesan);

                return response()->json([
                    'code'    => 'success',
                    'message' => 'Usulan berhasil dibuat',
                    'data'    => $usulan,
                ], 201);

            } catch (ValidationException $e) {
        return response()->json([
            'code' => 'validation_error',
            'message' => 'Data yang dikirim tidak valid',
            'errors' => $e->errors()
        ], 422);

        } catch (QueryException $e) {
            return response()->json([
                'code' => 'database_error',
                'message' => 'Terjadi kesalahan pada database'
            ], 500);

        } catch (HttpException $e) {
            return response()->json([
                'code' => 'http_error',
                'message' => $e->getMessage()
            ], $e->getStatusCode());

        } catch (\Throwable $e) {

            // LOG DETAIL UNTUK DEVELOPER
            \Log::error('Create Usulan Error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 'server_error',
                'message' => 'Terjadi kesalahan pada server, silakan coba lagi'
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        try {
            $usulan = Usulan::with(['subJenisBantuan', 'kategori', 'opd', 'desa', 'spj'])->findOrFail($id);

            // ✅ Authorization check

            return response()->json([
                'code'    => 'success',
                'message' => 'OK',
                'data'    => $usulan,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Usulan tidak ditemukan',
                'error'   => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal mengambil data usulan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getByOpd(String $kode_opd)
    {
        try {
            $usulan = Usulan::with(['subJenisBantuan', 'kategori', 'opd', 'desa', 'spj'])->where('kode_opd', $kode_opd)->get();

            // ✅ Authorization check


            return response()->json([
                'code'    => 'success',
                'message' => 'OK',
                'data'    => $usulan,
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized: Anda tidak memiliki akses ke usulan ini',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Usulan tidak ditemukan',
                'error'   => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal mengambil data usulan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, string $id)
    {
        try {
            $usulan = Usulan::findOrFail($id);

            // =========================
            // VALIDASI REQUEST
            // =========================
            $validated = $request->validate([
                'judul'              => ['sometimes', 'string', 'max:255'],
                'anggaran_usulan'    => ['sometimes', 'integer', 'min:0'],
                'status'             => ['sometimes', 'in:diusulkan,disetujui,diterima,ditolak'],
                'catatan_ditolak'    => ['sometimes', 'nullable', 'string', 'max:500'],
                'tahun'              => ['sometimes', 'digits:4'],
                'otp'                => ['required', 'digits:6'],
            ]);

            // =========================
            // VALIDASI OTP
            // =========================
            if (!validateOtp($usulan->nohp, $validated['otp'])) {
                return response()->json([
                    'code' => 'error',
                    'message' => 'OTP tidak valid atau sudah kedaluwarsa'
                ], 422);
            }

            unset($validated['otp']);

            // =========================
            // UPDATE DATA
            // =========================
            $usulan->update($validated);
            log_bantuan(['id_fk' => $usulan->idusulan]);

            // =========================
            // KIRIM LINK VIA WHATSAPP
            // =========================
            $hash = urlencode(
                Crypt::encryptString((string) $usulan->idusulan)
            );

            $link = url("/api/u/{$hash}");

            $pesan = "📄 *Usulan Anda Berhasil Diproses*\n\n"
                . "Judul: {$usulan->judul}\n"
                . "Tahun: {$usulan->tahun}\n\n"
                . "🔗 *Lihat detail usulan Anda di sini:*\n"
                . "{$link}\n\n"
                . "Simpan pesan ini untuk referensi Anda.";

            send_whatsapp(getTokenFonte(), $usulan->nohp, $pesan);

            return response()->json([
                'code'    => 'success',
                'message' => 'Usulan berhasil diperbarui',
                'data'    => $usulan->fresh(),
            ], 200);

        // =========================
        // VALIDATION ERROR
        // =========================
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 'validation_error',
                'message' => 'Data yang dikirim tidak valid',
                'errors' => $e->errors(),
            ], 422);

        // =========================
        // DATA TIDAK DITEMUKAN
        // =========================
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 'not_found',
                'message' => 'Usulan tidak ditemukan',
            ], 404);

        // =========================
        // DATABASE ERROR
        // =========================
        } catch (QueryException $e) {
            \Log::error('Update Usulan - DB Error', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 'database_error',
                'message' => 'Terjadi kesalahan pada database',
            ], 500);

        // =========================
        // HTTP / FORBIDDEN / THROTTLE
        // =========================
        } catch (HttpException $e) {
            return response()->json([
                'code' => 'http_error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());

        // =========================
        // ERROR TAK TERDUGA
        // =========================
        } catch (\Throwable $e) {

            \Log::error('Update Usulan - Server Error', [
                'id'    => $id,
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 'server_error',
                'message' => 'Gagal memperbarui usulan, silakan coba lagi',
            ], 500);
        }
    }


    public function updateStatus(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $usulan = Usulan::findOrFail($id);

            // Authorization
            $this->authorize('update', $usulan);

            // Validasi request
            $validated = $request->validate([
                'status' => ['required', Rule::in(['diusulkan', 'disetujui', 'diterima', 'ditolak'])],
            ]);

            // Update status
            $usulan->update($validated);
            log_bantuan(['id_fk' => $usulan->idusulan]);

            // ================================
            // 🔔 KIRIM NOTIFIKASI WHATSAPP
            // ================================
            $no_hp = $usulan->nohp;

            if ($no_hp) {
                $cek_valid_wa = json_decode(
                    validate_whatsapp(getTokenFonte(), $no_hp)
                );

                if ($cek_valid_wa && $cek_valid_wa->status) {
                    if (empty($cek_valid_wa->not_registered)) {

                        $pesan = match ($usulan->status) {
                            'disetujui' => "✅ *Usulan Anda Disetujui*\n\nJudul: {$usulan->judul}",
                            'ditolak'   => "❌ *Usulan Anda Ditolak*\n\nCatatan: {$usulan->catatan_ditolak}",
                            'diterima'  => "🎉 *Usulan Anda Diterima*",
                            default     => "📌 Status usulan diperbarui menjadi *{$usulan->status}*",
                        };

                        $send = json_decode(
                            send_whatsapp(getTokenFonte(), $no_hp, $pesan)
                        );
                        if (!$send || !$send->status) {
                            DB::commit();

                            return response()->json([
                                'code'    => 'warning',
                                'message' => 'Status berhasil diupdate, namun pesan WhatsApp gagal dikirim',
                                'data'    => $usulan->fresh(),
                            ], 200);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'code'    => 'success',
                'message' => 'Status usulan berhasil diperbarui & notifikasi dikirim',
                'data'    => $usulan->fresh(),
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            DB::rollBack();
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized',
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'code'    => 'error',
                'message' => 'Usulan tidak ditemukan',
            ], 404);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal memperbarui status usulan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'otp' => ['required', 'digits:6'],
            ]);

            $usulan = Usulan::findOrFail($id);

            // 🔐 VALIDASI OTP BERDASARKAN NOHP USULAN
            if (!validateOtp($usulan->nohp, $validated['otp'])) {
                return response()->json([
                    'code' => 'error',
                    'message' => 'OTP tidak valid atau sudah kedaluwarsa'
                ], 422);
            }

            $usulan->delete();
            log_bantuan(['id_fk' => $usulan->idusulan]);

            return response()->json([
                'code' => 'success',
                'message' => 'Usulan berhasil dihapus'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 'error',
                'message' => 'Usulan tidak ditemukan'
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'code' => 'error',
                'message' => 'Gagal menghapus usulan',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Approve or reject usulan
     */
    public function approve(Request $request, String $id)
    {
        try {
            $usulan = Usulan::findOrFail($id);

            // ✅ Authorization check - Admin & OPD bisa approve
            $this->authorize('approve', $usulan);

            $validated = $request->validate([
                'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
                'catatan_ditolak' => ['nullable', 'string', 'max:500']
            ], [
                'status.required' => 'Status wajib diisi',
                'status.in' => 'Status harus disetujui atau ditolak'
            ]);

            $usulan->update($validated);
            log_bantuan(['id_fk' => $usulan->idusulan]);

            return response()->json([
                'code'    => 'success',
                'message' => 'Usulan berhasil ' . $validated['status'],
                'data'    => $usulan->fresh(),
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized: Anda tidak memiliki akses untuk approve usulan',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Usulan tidak ditemukan',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal approve usulan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get log_usulan by tanggal, id_user, or id_usulan and their values.
     */
    public function getLogs(Request $request)
    {
        try {
            $query = \DB::table('usulan_log')
                ->join('usulan', 'usulan_log.idusulan', '=', 'usulan.idusulan')
                ->leftJoin('users', 'usulan_log.iduser', '=', 'users.id')
                ->select('usulan_log.*', 'usulan.judul as judul_usulan', 'users.name as nama_user');

            if ($request->filled('tanggal')) {
                $query->whereDate('usulan_log.tanggal', $request->input('tanggal'));
            }
            if ($request->filled('iduser')) {
                $query->where('usulan_log.iduser', $request->input('iduser'));
            }
            if ($request->filled('idusulan')) {
                $query->where('usulan_log.idusulan', $request->input('idusulan'));
            }

            $logs = $query->orderByDesc('usulan_log.tanggal')->get();

            return response()->json([
                'code'    => 'success',
                'message' => 'Log ditemukan',
                'data'    => $logs,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal mengambil log usulan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

   public function getSebaranAnggaranDisetujui(Request $request)
{
    /* =====================================================
     * PARAMETER
     * =====================================================*/
    $tahun = (int) $request->input('tahun', now()->year);
    $level = $request->input('level', 'kecamatan');

    $filterKec  = $request->input('filter_kecamatan');
    $filterDesa = $request->input('filter_desa');
    $filterSub  = $request->input('filter_subjenisbantuan');

    if (!in_array($level, ['kecamatan', 'desa', 'subjenisbantuan'])) {
        return response()->json([
            'success' => false,
            'message' => 'Level tidak valid'
        ], 422);
    }

    /* =====================================================
     * LEVEL KECAMATAN
     * =====================================================*/
    if ($level === 'kecamatan') {

        // master kecamatan
        $kecamatanRows = DB::table('kecamatan as k')
            ->when($filterKec, fn ($q) => $q->where('k.idkecamatan', $filterKec))
            ->select('k.idkecamatan', 'k.namakecamatan')
            ->orderBy('k.namakecamatan')
            ->get();

        // desa + total
        $desaRows = DB::table('desa as d')
            ->join('kecamatan as k', 'k.idkecamatan', '=', 'd.idkecamatan')
            ->leftJoin('usulan as u', function ($join) use ($tahun, $filterSub, $filterDesa) {
                $join->on('u.iddesa', '=', 'd.iddesa')
                    ->where('u.status', 'disetujui')
                    ->where('u.tahun', $tahun);

                if ($filterSub !== null) {
                    $join->where('u.idsubjenisbantuan', $filterSub);
                }

                if ($filterDesa !== null) {
                    $join->where('u.iddesa', $filterDesa);
                }
            })
            ->when($filterKec, fn ($q) => $q->where('k.idkecamatan', $filterKec))
            ->select(
                'k.idkecamatan',
                'd.iddesa',
                'd.namadesa',
                'd.latitude',
                'd.longitude',
                DB::raw('COALESCE(SUM(u.anggaran_disetujui),0) as total_anggaran_disetujui')
            )
            ->groupBy(
                'k.idkecamatan',
                'd.iddesa',
                'd.namadesa',
                'd.latitude',
                'd.longitude'
            )
            ->orderBy('d.namadesa')
            ->get()
            ->groupBy('idkecamatan');

        $data = $kecamatanRows->map(function ($kec) use ($desaRows) {
            $desaList = collect($desaRows->get($kec->idkecamatan, []));
            return [
                'idkecamatan'              => (int) $kec->idkecamatan,
                'namakecamatan'            => $kec->namakecamatan,
                'total_anggaran_disetujui' => (int) $desaList->sum('total_anggaran_disetujui'),
                'desa' => $desaList->map(fn ($d) => [
                    'iddesa'                   => (int) $d->iddesa,
                    'namadesa'                 => $d->namadesa,
                    'latitude'                 => $d->latitude ? (float) $d->latitude : null,
                    'longitude'                => $d->longitude ? (float) $d->longitude : null,
                    'total_anggaran_disetujui' => (int) $d->total_anggaran_disetujui,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Sebaran anggaran disetujui berdasarkan kecamatan',
            'data' => $data,
        ]);
    }

    /* =====================================================
     * LEVEL DESA
     * =====================================================*/
    if ($level === 'desa') {

        $desaRows = DB::table('desa as d')
            ->join('kecamatan as k', 'k.idkecamatan', '=', 'd.idkecamatan')
            ->leftJoin('usulan as u', function ($join) use ($tahun, $filterSub) {
                $join->on('u.iddesa', '=', 'd.iddesa')
                    ->where('u.status', 'disetujui')
                    ->where('u.tahun', $tahun);

                if ($filterSub !== null) {
                    $join->where('u.idsubjenisbantuan', $filterSub);
                }
            })
            ->when($filterKec, fn ($q) => $q->where('k.idkecamatan', $filterKec))
            ->when($filterDesa, fn ($q) => $q->where('d.iddesa', $filterDesa))
            ->select(
                'd.iddesa',
                'd.namadesa',
                'k.idkecamatan',
                'k.namakecamatan',
                'd.latitude',
                'd.longitude',
                DB::raw('COALESCE(SUM(u.anggaran_disetujui),0) as total_anggaran_disetujui')
            )
            ->groupBy(
                'd.iddesa',
                'd.namadesa',
                'k.idkecamatan',
                'k.namakecamatan',
                'd.latitude',
                'd.longitude'
            )
            ->orderBy('d.namadesa')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Sebaran anggaran disetujui berdasarkan desa',
            'data' => $desaRows->map(fn ($d) => [
                'iddesa'                   => (int) $d->iddesa,
                'namadesa'                 => $d->namadesa,
                'idkecamatan'              => (int) $d->idkecamatan,
                'namakecamatan'            => $d->namakecamatan,
                'latitude'                 => $d->latitude ? (float) $d->latitude : null,
                'longitude'                => $d->longitude ? (float) $d->longitude : null,
                'total_anggaran_disetujui' => (int) $d->total_anggaran_disetujui,
            ]),
        ]);
    }

    /* =====================================================
     * LEVEL SUB JENIS BANTUAN
     * =====================================================*/
    if ($level === 'subjenisbantuan') {

        $subRows = DB::table('sub_jenis_bantuan as s')
            ->leftJoin('usulan as u', function ($join) use ($tahun, $filterDesa) {
                $join->on('u.idsubjenisbantuan', '=', 's.idsubjenisbantuan')
                    ->where('u.status', 'disetujui')
                    ->where('u.tahun', $tahun);

                if ($filterDesa !== null) {
                    $join->where('u.iddesa', $filterDesa);
                }
            })
            ->when($filterSub, fn ($q) => $q->where('s.idsubjenisbantuan', $filterSub))
            ->select(
                's.idsubjenisbantuan',
                's.namasubjenis',
                DB::raw('COALESCE(SUM(u.anggaran_disetujui),0) as total_anggaran_disetujui')
            )
            ->groupBy('s.idsubjenisbantuan', 's.namasubjenis')
            ->orderBy('s.namasubjenis')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Sebaran anggaran disetujui berdasarkan sub jenis bantuan',
            'data' => $subRows->map(fn ($s) => [
                'idsubjenisbantuan'        => (int) $s->idsubjenisbantuan,
                'namasubjenis'             => $s->namasubjenis,
                'total_anggaran_disetujui' => (int) $s->total_anggaran_disetujui,
            ]),
        ]);
    }
}




    /**
     * Default range: YTD (awal tahun - sekarang) bila salah satu/begitu saja.
     */
    private function resolveBetweenRange(?string $start, ?string $end): array
    {
        if (!$start && !$end) {
            $y = now()->format('Y');
            return ["$y-01-01 00:00:00", now()->format('Y-m-d H:i:s')];
        }
        if ($start && !$end) {
            return [$start, now()->format('Y-m-d H:i:s')];
        }
        if (!$start && $end) {
            $y = now()->format('Y');
            return ["$y-01-01 00:00:00", $end];
        }
        return [$start, $end];
    }
}
