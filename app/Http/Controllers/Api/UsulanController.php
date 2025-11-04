<?php

namespace App\Http\Controllers\Api;

use App\Models\Usulan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UsulanController
{
    public function index(Request $request)
    {
        // 🔍 Search global
        $query = Usulan::with(['subJenisBantuan', 'kategori' , 'opd', 'desa']);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('anggaran_usulan', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nohp', 'like', "%{$search}%")
                  ->orWhere('anggaran_disetujui', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('anggaran_usulan', 'like', "%{$search}%")
                  ->orWhereHas('subJenisBantuan', function ($qq) use ($search) {
                        $qq->where('namasubjenis', 'like', "%{$search}%");
                    })
                     // 🔎 cari di relasi kategori
                    ->orWhereHas('kategori', function ($qq) use ($search) {
                        $qq->where('namakategori', 'like', "%{$search}%");
                    })

                    // 🔎 cari di relasi opd
                    ->orWhereHas('opd', function ($qq) use ($search) {
                        $qq->where('nama_opd', 'like', "%{$search}%");
                    })

                    // 🔎 cari di relasi desa
                    ->orWhereHas('desa', function ($qq) use ($search) {
                        $qq->where('namadesa', 'like', "%{$search}%");
                    });

            });
        }

        // 🔽 Sorting
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');

        $allowedSorts = [
            'idusulan',
            'judul',
            'anggaran_usulan',
            'anggaran_disetujui',
            'email',
            'nohp',
            'status',
            'nama',
            'subJenisBantuan.namasubjenis',
            'kategori.namakategori',
            'opd.nama_opd',
            'desa.namadesa'
        ];

        // Handle sorting
        if (in_array($sortBy, $allowedSorts)) {
            if (str_contains($sortBy, '.')) {
            // Handle relation sorting
            [$relation, $column] = explode('.', $sortBy);
            if ($relation === 'opd') {
                $query->join($relation, "usulan.kode_opd", '=', "opd.kode_opd")
                  ->orderBy("{$relation}.{$column}", $sortDir)
                  ->select('usulan.*');
            } else {
                $query->join($relation, "usulan.id{$relation}", '=', "{$relation}.id{$relation}")
                  ->orderBy("{$relation}.{$column}", $sortDir)
                  ->select('usulan.*');
            }
            } else {
            $query->orderBy($sortBy, $sortDir);
            }
        }

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        // 📄 Pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $usulan = $query->paginate($perPage, ['*'], 'page', $page);

        // 🖼️ Ubah URL file_persyaratan jadi absolute path
        $usulan->getCollection()->transform(function ($item) {
            $item->file_persyaratan = $item->file_persyaratan
                ? asset("storage/uploads/{$item->file_persyaratan}")
                : null;
            return $item;
        });

        // 📦 Kembalikan response terstruktur
        return response()->json([
            'data' => $usulan->items(),
            'meta' => [
                'page'        => $usulan->currentPage(),
                'per_page'    => $usulan->perPage(),
                'total'       => $usulan->total(),
                'total_pages' => $usulan->lastPage(),
                'sort_by'     => $sortBy,
                'sort_dir'    => $sortDir,
                'search'      => $search,
            ]
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

                // file rar/zip max 2MB
                'file_persyaratan'   => ['required', 'file', 'max:2048', 'mimes:zip,rar'],

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
    public function show(Usulan $usulan)
    {
        $usulan->file_persyaratan = $usulan->file_persyaratan ? Storage::url('uploads/' . $usulan->file_persyaratan) : null;
        return response()->json([
            'code'    => 'success',
            'message' => 'OK',
            'data'    => $usulan,
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Usulan $usulan)
    {
          try {
            $validated = $request->validate([
                'judul'              => ['sometimes', 'string', 'max:255'],
                'anggaran_usulan'    => ['sometimes', 'integer', 'min:0'],
                'anggaran_disetujui' => ['sometimes', 'integer', 'min:0'],
                'file_persyaratan'   => ['sometimes', 'file', 'max:2048', 'mimes:zip,rar'],
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

        } catch (Throwable $e) {
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
    public function destroy(Usulan $usulan)
    {
        try {
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
        } catch (Throwable $e) {
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
        $query = \DB::table('usulan_log');

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->input('tanggal'));
        }
        if ($request->filled('id_user')) {
            $query->where('id_user', $request->input('id_user'));
        }
        if ($request->filled('id_usulan')) {
            $query->where('id_usulan', $request->input('id_usulan'));
        }

        $logs = $query->orderByDesc('tanggal')->get();

        return response()->json([
            'code'    => 'success',
            'message' => 'Log ditemukan',
            'data'    => $logs,
        ], 200);
    }
}
