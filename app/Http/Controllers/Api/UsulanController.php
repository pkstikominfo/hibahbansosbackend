<?php

namespace App\Http\Controllers\Api;

use App\Models\Usulan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
            'email', 'nohp', 'status', 'nama'
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
                'anggaran_disetujui' => ['required', 'integer', 'min:0'],

                // file max 2MB
                'file_persyaratan'   => ['required', 'file', 'max:2048'],

                'email'              => ['required', 'email', 'max:50'],
                'nohp'               => ['required', 'string', 'max:15'],
                'nama'               => ['required', 'string', 'max:100'],

                // ✅ ENUM status
                'status'             => ['required', Rule::in(['diusulkan', 'disetujui'])],

                // ✅ FK harus exist
                // idsubjenisbantuan → tabel sub_jenis_bantuan (kolom idsubjenisbantuan)
                'idsubjenisbantuan'  => ['required', 'integer', 'exists:sub_jenis_bantuan,idsubjenisbantuan'],

                // idkategori → tabel kategori (kolom idkategori)
                'idkategori'         => ['required', 'integer', 'exists:kategori,idkategori'],

                // iddesa → tabel desa (kolom iddesa)
                'iddesa'             => ['required', 'integer', 'exists:desa,iddesa'],

                // kode_opd → tabel opd (kolom kode_opd) — biasanya string
                'kode_opd'           => ['required', 'string', 'exists:opd,kode_opd'],
            ]);

            $file = $request->file('file_persyaratan');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads', $filename, 'public'); // simpan ke storage/app/public/uploads

            // masukkan nama atau path file ke data yang akan disimpan
            $validated['file_persyaratan'] = $filename; // atau: Storage::url($path) jika ingin menyimpan URL publik

            $usulan = Usulan::create($validated);
            log_usulan(['idusulan' => $usulan->idusulan]);

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
    public function update(Request $request, String $id)
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
                'nama'               => ['sometimes', 'string', 'max:50'],
                'status'             => ['sometimes', Rule::in(['diusulkan', 'disetujui'])],
                'idsubjenisbantuan'  => ['sometimes', 'integer', 'exists:sub_jenis_bantuan,idsubjenisbantuan'],
                'idkategori'         => ['sometimes', 'integer', 'exists:kategori,idkategori'],
                'iddesa'             => ['sometimes', 'integer', 'exists:desa,iddesa'],
                'kode_opd'           => ['sometimes', 'string', 'exists:opd,kode_opd'],
            ]);

            if ($request->hasFile('file_persyaratan')) {
                // hapus file lama jika ada
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
            log_usulan(['idusulan' => $usulan->idusulan]);
            return response()->json([
                'message' => 'Usulan berhasil diperbarui',
                'data'    => $usulan->fresh(),
            ], 200);

        } catch (Throwable|ModelNotFoundException $e) {
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
            log_usulan(['idusulan' => $usulan->idusulan]);
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
     * Get log_usulan by tanggal, id_user, or id_usulan and their values.
     * Example query: /api/usulan/logs?tanggal=2024-06-01&id_user=5&id_usulan=10
     */
    public function getLogs(Request $request)
    {
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
    }
}
