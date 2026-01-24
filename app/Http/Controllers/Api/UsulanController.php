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


class UsulanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource with authorization filter
     */
    public function index(Request $request)
    {
        try {
            // ✅ Authorization check
            $this->authorize('viewAny', Usulan::class);

            $user = $request->user();
            $query = Usulan::with(['subJenisBantuan', 'kategori', 'opd', 'desa']);

            // ✅ Filter berdasarkan role
            if ($user->isPengusul()) {
                // Pengusul hanya lihat usulan sendiri
                $query->where('email', $user->email);
            } elseif ($user->isOpd()) {
                // OPD lihat unassigned atau OPD sendiri
                $query->where(function ($q) use ($user) {
                    $q->whereNull('kode_opd')
                        ->orWhere('kode_opd', $user->kode_opd);
                });
            }
            // Admin lihat semua (no filter)

            // 🔍 Search global
            if ($search = $request->input('q')) {
                $query->where(function ($q) use ($search) {
                    $q->where('judul', 'like', "%{$search}%")
                        ->orWhere('idusulan', 'like', "%{$search}%")
                        ->orWhere('anggaran_usulan', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nohp', 'like', "%{$search}%")
                        ->orWhere('anggaran_disetujui', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('tahun', 'like', "%{$search}%")
                        ->orWhereHas('subJenisBantuan', fn($qq) => $qq->where('namasubjenis', 'like', "%{$search}%"))
                        ->orWhereHas('kategori', fn($qq) => $qq->where('namakategori', 'like', "%{$search}%"))
                        ->orWhereHas('opd', fn($qq) => $qq->where('nama_opd', 'like', "%{$search}%"))
                        ->orWhereHas('desa', fn($qq) => $qq->where('namadesa', 'like', "%{$search}%"));
                });
            }

            // 🔽 Sorting
            $sortBy  = $request->input('sort_by', 'idusulan');
            $sortDir = $request->input('sort_dir', 'asc');

            $directSorts = [
                'idusulan',
                'judul',
                'anggaran_usulan',
                'anggaran_disetujui',
                'email',
                'nohp',
                'status',
                'nama',
                'tahun',
            ];

            $relationSorts = [
                'subjenis' => ['table' => 'sub_jenis_bantuan', 'column' => 'namasubjenis', 'local_key' => 'idsubjenisbantuan', 'foreign_key' => 'idsubjenisbantuan'],
                'kategori' => ['table' => 'kategori',           'column' => 'namakategori',  'local_key' => 'idkategori',        'foreign_key' => 'idkategori'],
                'opd'      => ['table' => 'opd',                'column' => 'nama_opd',      'local_key' => 'kode_opd',          'foreign_key' => 'kode_opd'],
                'desa'     => ['table' => 'desa',               'column' => 'namadesa',      'local_key' => 'iddesa',            'foreign_key' => 'iddesa'],
            ];

            if (in_array($sortBy, $directSorts)) {
                $query->orderBy("usulan.$sortBy", $sortDir);
            } elseif (array_key_exists($sortBy, $relationSorts)) {
                [$table, $column, $local, $foreign] = [
                    $relationSorts[$sortBy]['table'],
                    $relationSorts[$sortBy]['column'],
                    $relationSorts[$sortBy]['local_key'],
                    $relationSorts[$sortBy]['foreign_key'],
                ];

                $query->leftJoin($table, "usulan.$local", '=', "$table.$foreign")
                    ->orderBy("$table.$column", $sortDir)
                    ->select('usulan.*');
            } else {
                $query->orderBy('usulan.idusulan', 'asc');
            }

            // 🔄 Pagination
            $perPage = (int) $request->input('per_page', 10);
            $page    = (int) $request->input('page', 1);

            $usulan = $query->paginate($perPage, ['*'], 'page', $page);


            return response()->json([
                'code' => 'success',
                'data' => $usulan->items(),
                'meta' => [
                    'page'        => $usulan->currentPage(),
                    'per_page'    => $usulan->perPage(),
                    'total'       => $usulan->total(),
                    'total_pages' => $usulan->lastPage(),
                    'sort_by'     => $sortBy,
                    'sort_dir'    => $sortDir,
                    'search'      => $search ?? null,
                ],
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized: ' . $e->getMessage(),
            ], 403);
        } catch (Throwable $e) {
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
            // ✅ Authorization check - hanya pengusul yang bisa buat usulan

            $validated = $request->validate([
                'judul'              => ['required', 'string', 'max:255'],
                'anggaran_usulan'    => ['required', 'integer', 'min:0'],
                'anggaran_disetujui' => ['nullable', 'integer', 'min:0'],
                'email'              => ['required', 'email', 'max:50'],
                'nohp'               => ['required', 'string', 'max:15'],
                'nama'               => ['required', 'string', 'max:100'],
                'status'             => ['required', Rule::in(['diusulkan', 'disetujui', 'diterima', 'ditolak'])],
                'idsubjenisbantuan'  => ['required', 'integer', 'exists:sub_jenis_bantuan,idsubjenisbantuan'],
                'idkategori'         => ['required', 'integer', 'exists:kategori,idkategori'],
                'iddesa'             => ['required', 'integer', 'exists:desa,iddesa'],
                'kode_opd'           => ['required', 'string', 'exists:opd,kode_opd'],
                'tahun'              => ['required', 'digits:4', 'integer', 'min:1900'],
            ]);


            // ===== Simpan data
            $usulan = Usulan::create($validated);
            log_bantuan(['id_fk' => $usulan->idusulan]);

            return response()->json([
                'code'    => 'success',
                'message' => 'Usulan berhasil dibuat',
                'data'    => $usulan,
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized: Hanya pengusul yang dapat membuat usulan',
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal membuat Usulan',
                'error'   => $e->getMessage(),
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
            $this->authorize('view', $usulan);


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
        DB::beginTransaction();

        try {
            $usulan = Usulan::findOrFail($id);

            // Authorization
            $this->authorize('update', $usulan);

            // Simpan status lama
            $oldStatus = $usulan->status;

            // Validasi
            $validated = $request->validate([
                'judul'              => ['sometimes', 'string', 'max:255'],
                'anggaran_usulan'    => ['sometimes', 'integer', 'min:0'],
                'anggaran_disetujui' => ['sometimes', 'integer', 'min:0'],
                'email'              => ['sometimes', 'email', 'max:50'],
                'nohp'               => ['sometimes', 'string', 'max:15'],
                'nama'               => ['sometimes', 'string', 'max:100'],
                'status'             => ['sometimes', Rule::in(['diusulkan', 'disetujui', 'diterima', 'ditolak'])],
                'idsubjenisbantuan'  => ['sometimes', 'integer', 'exists:sub_jenis_bantuan,idsubjenisbantuan'],
                'idkategori'         => ['sometimes', 'integer', 'exists:kategori,idkategori'],
                'iddesa'             => ['sometimes', 'integer', 'exists:desa,iddesa'],
                'kode_opd'           => ['sometimes', 'string', 'exists:opd,kode_opd'],
                'catatan_ditolak'    => ['sometimes', 'string', 'max:500'],
                'tahun'              => ['sometimes', 'digits:4'],
            ]);

            // Update data
            $usulan->update($validated);
            log_bantuan(['id_fk' => $usulan->idusulan]);

            // ===============================
            // 🔔 KIRIM WHATSAPP JIKA STATUS BERUBAH
            // ===============================
            if (
                array_key_exists('status', $validated) &&
                $oldStatus !== $validated['status']
            ) {
                $no_hp = $usulan->nohp;

                if ($no_hp) {
                    $cek_valid_wa = json_decode(
                        validate_whatsapp(getTokenFonte(), $no_hp)
                    );

                    if ($cek_valid_wa && $cek_valid_wa->status) {
                        if (empty($cek_valid_wa->not_registered)) {

                            $pesan = match ($usulan->status) {
                                'disetujui' =>
                                    "✅ *Usulan Disetujui*\n\nJudul: {$usulan->judul}",

                                'diterima' =>
                                    "🎉 *Usulan Diterima*\n\nJudul: {$usulan->judul}",

                                'ditolak' =>
                                    "❌ *Usulan Ditolak*\n\nCatatan:\n{$usulan->catatan_ditolak}",

                                default =>
                                    "📌 Status usulan diperbarui menjadi *{$usulan->status}*",
                            };

                            // Kirim WA
                            send_whatsapp(
                                getTokenFonte(),
                                $no_hp,
                                $pesan
                            );
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'code'    => 'success',
                'message' => 'Usulan berhasil diperbarui',
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
                'message' => 'Gagal memperbarui usulan',
                'error'   => $e->getMessage(),
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
    public function destroy(String $id)
    {
        try {
            $usulan = Usulan::findOrFail($id);

            // ✅ Authorization check - hanya admin yang bisa hapus
            $this->authorize('delete', $usulan);


            $usulan->delete();
            log_bantuan(['id_fk' => $usulan->idusulan]);
            return response()->json([
                'code'    => 'success',
                'message' => 'Usulan berhasil dihapus',
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized: Hanya admin yang dapat menghapus usulan',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Usulan tidak ditemukan',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal menghapus usulan',
                'error'   => $e->getMessage(),
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
