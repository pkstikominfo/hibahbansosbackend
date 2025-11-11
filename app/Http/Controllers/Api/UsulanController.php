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
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;

class UsulanController
{
    public function index(Request $request)
    {
        $query = Usulan::with(['subJenisBantuan', 'kategori', 'opd', 'desa']);

        // 🔍 Search global (tetap punyamu)
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
                ->orWhere('no_sk', 'like', "%{$search}%")
                ->orWhere('nama_lembaga', 'like', "%{$search}%")
                ->orWhere('created_at', 'like', "%{$search}%")
                ->orWhereHas('subJenisBantuan', fn($qq) => $qq->where('namasubjenis', 'like', "%{$search}%"))
                ->orWhereHas('kategori', fn($qq) => $qq->where('namakategori', 'like', "%{$search}%"))
                ->orWhereHas('opd', fn($qq) => $qq->where('nama_opd', 'like', "%{$search}%"))
                ->orWhereHas('desa', fn($qq) => $qq->where('namadesa', 'like', "%{$search}%"));
            });
        }

        // 🔽 Sorting
        $sortBy  = $request->input('sort_by', 'idusulan');
        $sortDir = $request->input('sort_dir', 'asc');

        // kolom langsung di tabel usulan
        $directSorts = [
            'idusulan', 'judul', 'anggaran_usulan', 'anggaran_disetujui',
            'email', 'nohp', 'status', 'nama', 'no_sk', 'nama_lembaga', 'created_at'
        ];

        // mapping sort relasi => [table, column, local_key, foreign_key]
        $relationSorts = [
            // kamu bisa ganti 'sub_jenis_bantuan' sesuai nama tabel aslinya
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

            // LEFT JOIN supaya data usulan tanpa relasi tetap ikut
            $query->leftJoin($table, "usulan.$local", '=', "$table.$foreign")
                ->orderBy("$table.$column", $sortDir)
                ->select('usulan.*'); // hindari ambiguitas kolom
        } else {
            // fallback aman
            $query->orderBy('usulan.idusulan', 'asc');
        }

        // 📄 Pagination
        $perPage = (int) $request->input('per_page', 10);
        $page    = (int) $request->input('page', 1);

        $usulan = $query->paginate($perPage, ['*'], 'page', $page);

        // 🖼️ absolute URL untuk file
        $usulan->getCollection()->transform(function ($item) {
            $item->file_persyaratan = $item->file_persyaratan
                ? asset("storage/uploads/{$item->file_persyaratan}")
                : null;
            return $item;
        });

        return response()->json([
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
    }


    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'judul'              => ['required', 'string', 'max:255'],
                'anggaran_usulan'    => ['required', 'integer', 'min:0'],
                'anggaran_disetujui' => ['nullable', 'integer', 'min:0'],
                'file_persyaratan'   => ['required', 'file', 'max:2048'],
                'email'              => ['required', 'email', 'max:50'],
                'nohp'               => ['required', 'string', 'max:15'],
                'nama'               => ['required', 'string', 'max:100'],
                'status'             => ['required', Rule::in(['diusulkan', 'disetujui'])],
                'idsubjenisbantuan'  => ['required', 'integer', 'exists:sub_jenis_bantuan,idsubjenisbantuan'],
                'idkategori'         => ['required', 'integer', 'exists:kategori,idkategori'],
                'iddesa'             => ['required', 'integer', 'exists:desa,iddesa'],
                'kode_opd'           => ['string', 'exists:opd,kode_opd'],
                'no_sk'              => [
                    'required',
                    'string',
                    'max:75',
                    // ✅ custom rule: hanya boleh 1x per tahun
                    function ($attribute, $value, $fail) {
                        $tahunSekarang = date('Y');
                        $ada = Usulan::where('no_sk', $value)
                            ->whereYear('created_at', $tahunSekarang)
                            ->exists();

                        if ($ada) {
                            $fail("Nomor SK $value sudah digunakan pada tahun $tahunSekarang.");
                        }
                    },
                ],
                'nama_lembaga'       => ['required', 'string', 'max:75'],
            ]);

            // ===== Simpan file
            $file = $request->file('file_persyaratan');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads', $filename, 'public');
            $validated['file_persyaratan'] = $filename;

            // ===== Simpan data
            $usulan = Usulan::create($validated);
            log_bantuan(['id_fk' => $usulan->idusulan]);

            return response()->json([
                'code'    => 'success',
                'message' => 'Usulan berhasil dibuat',
                'data'    => $usulan,
            ], 201);
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
    public function show( String $id)
    {
        try {
            $usulan = Usulan::with(['subJenisBantuan', 'kategori', 'opd', 'desa', 'spj'])->findOrFail($id);
            $usulan->file_persyaratan = $usulan->file_persyaratan ? Storage::url('uploads/' . $usulan->file_persyaratan) : null;
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
        }
    }


    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, string $id)
    {
        try {
            $usulan = Usulan::findOrFail($id);

            $validated = $request->validate([
                'judul'              => ['sometimes', 'string', 'max:255'],
                'anggaran_usulan'    => ['sometimes', 'integer', 'min:0'],
                'anggaran_disetujui' => ['sometimes', 'integer', 'min:0'],
                'file_persyaratan'   => ['sometimes', 'file', 'max:2048'],
                'email'              => ['sometimes', 'email', 'max:50'],
                'nohp'               => ['sometimes', 'string', 'max:15'],
                'nama'               => ['sometimes', 'string', 'max:100'],
                'status'             => ['sometimes', Rule::in(['diusulkan', 'disetujui'])],
                'idsubjenisbantuan'  => ['sometimes', 'integer', 'exists:sub_jenis_bantuan,idsubjenisbantuan'],
                'idkategori'         => ['sometimes', 'integer', 'exists:kategori,idkategori'],
                'iddesa'             => ['sometimes', 'integer', 'exists:desa,iddesa'],
                'kode_opd'           => ['sometimes', 'string', 'exists:opd,kode_opd'],
                'nama_lembaga'      => ['sometimes', 'string', 'max:100'],

                // ✅ Validasi: no_sk hanya boleh 1x per tahun (kecuali record ini sendiri)
                'no_sk' => [
                    'sometimes',
                    'string',
                    'max:75',
                    function ($attribute, $value, $fail) use ($usulan, $request) {
                        // Tahun yang dipakai untuk pembatasan (umumnya tahun berjalan)
                        $tahun = date('Y');

                        $exists = Usulan::where('no_sk', $value)
                            ->whereYear('created_at', $tahun)
                            ->where('idusulan', '!=', $usulan->idusulan) // abaikan record yang sedang diupdate
                            ->exists();

                        if ($exists) {
                            $fail("Nomor SK {$value} sudah digunakan pada tahun {$tahun}.");
                        }
                    },
                ],
            ]);

            // ==== Handle file jika diupload ulang
            if ($request->hasFile('file_persyaratan')) {
                $oldFile = (string) $usulan->file_persyaratan;
                if ($oldFile && Storage::exists('public/uploads/' . $oldFile)) {
                    Storage::delete('public/uploads/' . $oldFile);
                }

                $file = $request->file('file_persyaratan');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads', $filename, 'public');
                $validated['file_persyaratan'] = $filename;
            }

            $usulan->update($validated);
            log_bantuan(['id_fk' => $usulan->idusulan]);

            return response()->json([
                'message' => 'Usulan berhasil diperbarui',
                'data'    => $usulan->fresh(),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal memperbarui layanan',
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
            // hapus file dari storage jika ada
            if ($usulan->file_persyaratan && Storage::exists('public/uploads/' . $usulan->file_persyaratan)) {
                Storage::delete('public/uploads/' . $usulan->file_persyaratan);
            }

            $usulan->delete();
            log_bantuan(['id_fk' => $usulan->idusulan]);
            return response()->json([
                'code'    => 'success',
                'message' => 'Usulan berhasil dihapus',
            ], 200);
        } catch (Throwable|ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal menghapus usulan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get log_bantuan by tanggal, id_user, or id_usulan and their values.
     * Example query: /api/usulan/logs?tanggal=2024-06-01&id_user=5&id_usulan=10
     */
    public function getLogs(Request $request)
    {
        $query = \DB::table('bantuan_log')
            ->leftJoin('usulan', 'bantuan_log.id_fk', '=', 'usulan.idusulan')
            ->leftJoin('spj', 'bantuan_log.id_fk', '=', 'spj.idspj')
            ->leftJoin('users', 'bantuan_log.iduser', '=', 'users.id')
            ->select('bantuan_log.*', 'usulan.judul as judul_usulan', 'users.name as nama_user');
        if ($request->filled('jenis')) {
            $query->where('bantuan_log.jenis', $request->input('jenis'));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('bantuan_log.tanggal', $request->input('tanggal'));
        }
        if ($request->filled('iduser')) {
            $query->where('bantuan_log.iduser', $request->input('iduser'));
        }
        if ($request->filled('id_fk')) {
            $query->where('bantuan_log.id_fk', $request->input('id_fk'));
        }

        $logs = $query->orderByDesc('bantuan_log.tanggal')->get();

        return response()->json([
            'code'    => 'success',
            'message' => 'Log ditemukan',
            'data'    => $logs,
        ], 200);
    }

     public function getSebaranAnggaranDisetujui(Request $request)
    {
        $level = $request->input('level', 'kecamatan'); // default
        $betweenCol = $request->input('between_column', 'created_at');
        [$start, $end] = $this->resolveBetweenRange(
            $request->input('between_start'),
            $request->input('between_end')
        );

        // Filter khusus sesuai level
        $idKec  = $request->input('idkecamatan');
        $idDesa = $request->input('iddesa');
        $idSub  = $request->input('idsubjenisbantuan');

        // Filter bebas (opsional)
        $whereCol = $request->input('where_column');
        $whereVal = $request->input('where_value');

        // Switch per level
        switch ($level) {
            case 'desa':
                $rows = DB::table('usulan as u')
                    ->join('desa as d', 'd.iddesa', '=', 'u.iddesa')
                    ->select(
                        'd.iddesa',
                        'd.namadesa',
                        DB::raw('SUM(u.anggaran_disetujui) as total_anggaran_disetujui')
                    )
                    ->when($idDesa, fn($q) => $q->where('d.iddesa', $idDesa))
                    ->when($idKec,  fn($q) => $q->where('d.idkecamatan', $idKec))
                    ->when($start && $end, fn($q) => $q->whereBetween("u.$betweenCol", [$start, $end]))
                    ->when($whereCol && $whereVal !== null, function ($q) use ($whereCol, $whereVal) {
                        if (is_array($whereVal)) {
                            $q->whereIn($whereCol, $whereVal);
                        } else {
                            $q->where($whereCol, $whereVal);
                        }
                    })
                    ->groupBy('d.iddesa', 'd.namadesa')
                    ->orderBy('d.namadesa')
                    ->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Sebaran anggaran disetujui berdasarkan kategori: desa',
                    'data'    => $rows->map(function ($r) {
                        return [
                            'iddesa'                   => (int) $r->iddesa,
                            'namadesa'                 => $r->namadesa,
                            'total_anggaran_disetujui' => (int) $r->total_anggaran_disetujui,
                        ];
                    }),
                ]);

            case 'subjenisbantuan':
                $rows = DB::table('usulan as u')
                    ->join('sub_jenis_bantuan as s', 's.idsubjenisbantuan', '=', 'u.idsubjenisbantuan')
                    ->select(
                        's.idsubjenisbantuan',
                        's.namasubjenis',
                        DB::raw('SUM(u.anggaran_disetujui) as total_anggaran_disetujui')
                    )
                    ->when($idSub, fn($q) => $q->where('s.idsubjenisbantuan', $idSub))
                    ->when($start && $end, fn($q) => $q->whereBetween("u.$betweenCol", [$start, $end]))
                    ->when($whereCol && $whereVal !== null, function ($q) use ($whereCol, $whereVal) {
                        if (is_array($whereVal)) {
                            $q->whereIn($whereCol, $whereVal);
                        } else {
                            $q->where($whereCol, $whereVal);
                        }
                    })
                    ->groupBy('s.idsubjenisbantuan', 's.namasubjenis')
                    ->orderBy('s.namasubjenis')
                    ->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Sebaran anggaran disetujui berdasarkan kategori: subjenisbantuan',
                    'data'    => $rows->map(function ($r) {
                        return [
                            'idsubjenisbantuan'        => (int) $r->idsubjenisbantuan,
                            'namasubjenis'             => $r->namasubjenis,
                            'total_anggaran_disetujui' => (int) $r->total_anggaran_disetujui,
                        ];
                    }),
                ]);

            case 'kecamatan':
            default:
                // Ambil total per kecamatan
                $kecamatanRows = DB::table('usulan as u')
                    ->join('desa as d', 'd.iddesa', '=', 'u.iddesa')
                    ->join('kecamatan as k', 'k.idkecamatan', '=', 'd.idkecamatan')
                    ->select(
                        'k.idkecamatan',
                        'k.namakecamatan',
                        DB::raw('SUM(u.anggaran_disetujui) as total_anggaran_disetujui')
                    )
                    ->when($idKec, fn($q) => $q->where('k.idkecamatan', $idKec))
                    ->when($start && $end, fn($q) => $q->whereBetween("u.$betweenCol", [$start, $end]))
                    ->when($whereCol && $whereVal !== null, function ($q) use ($whereCol, $whereVal) {
                        if (is_array($whereVal)) {
                            $q->whereIn($whereCol, $whereVal);
                        } else {
                            $q->where($whereCol, $whereVal);
                        }
                    })
                    ->groupBy('k.idkecamatan', 'k.namakecamatan')
                    ->orderBy('k.namakecamatan')
                    ->get();

                // Ambil anak: total per desa (untuk kecamatan terfilter)
                $desaRows = DB::table('usulan as u')
                    ->join('desa as d', 'd.iddesa', '=', 'u.iddesa')
                    ->join('kecamatan as k', 'k.idkecamatan', '=', 'd.idkecamatan')
                    ->select(
                        'k.idkecamatan',
                        'd.iddesa',
                        'd.namadesa',
                        DB::raw('SUM(u.anggaran_disetujui) as total_anggaran_disetujui')
                    )
                    ->when($idKec, fn($q) => $q->where('k.idkecamatan', $idKec))
                    ->when($start && $end, fn($q) => $q->whereBetween("u.$betweenCol", [$start, $end]))
                    ->when($whereCol && $whereVal !== null, function ($q) use ($whereCol, $whereVal) {
                        if (is_array($whereVal)) {
                            $q->whereIn($whereCol, $whereVal);
                        } else {
                            $q->where($whereCol, $whereVal);
                        }
                    })
                    ->groupBy('k.idkecamatan', 'd.iddesa', 'd.namadesa')
                    ->orderBy('d.namadesa')
                    ->get()
                    ->groupBy('idkecamatan');

                $data = $kecamatanRows->map(function ($kec) use ($desaRows) {
                    $anakDesa = collect($desaRows->get($kec->idkecamatan, []))->map(function ($d) {
                        return [
                            'iddesa'                   => (int) $d->iddesa,
                            'namadesa'                 => $d->namadesa,
                            'total_anggaran_disetujui' => (int) $d->total_anggaran_disetujui,
                        ];
                    })->values();

                    return [
                        'idkecamatan'              => (int) $kec->idkecamatan,
                        'namakecamatan'            => $kec->namakecamatan,
                        'total_anggaran_disetujui' => (int) $kec->total_anggaran_disetujui,
                        'desa'                     => $anakDesa,
                    ];
                })->values();

                return response()->json([
                    'success' => true,
                    'message' => 'Sebaran anggaran disetujui berdasarkan kategori: kecamatan',
                    'data'    => $data,
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
