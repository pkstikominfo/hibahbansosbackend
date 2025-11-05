<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Spj;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class SpjController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Spj::with(['usulan']);

        // 🔍 Search global (tetap punyamu)
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('idspj', 'like', "%{$search}%")
                ->orWhere('realisasi', 'like', "%{$search}%")
                ->whereHas('usulan', fn($qq) => $qq->where('judul', 'like', "%{$search}%"));
            });
        }

        // 🔽 Sorting
        $sortBy  = $request->input('sort_by', 'idspsj');
        $sortDir = $request->input('sort_dir', 'asc');

        // kolom langsung di tabel usulan
        $directSorts = [
            'idspj', 'realisasi'
        ];

        // mapping sort relasi => [table, column, local_key, foreign_key]
        $relationSorts = [
            // kamu bisa ganti 'sub_jenis_bantuan' sesuai nama tabel aslinya
            'judul' => ['table' => 'usulan', 'column' => 'judul', 'local_key' => 'idusulan', 'foreign_key' => 'idusulan']];

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
            $query->orderBy('idspj', 'asc');
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
                'idusulan' => ['required', 'integer', 'exists:usulan,idusulan'],

                // file rar/zip max 2MB
                'file_pertanggungjawaban'   => ['required', 'file', 'max:2048'],
                'foto'   => ['required', 'file', 'max:2048', 'mimes:jpg,jpeg,png'],

                'realisasi'              => ['required', 'integer', 'min:0'],

                // ✅ ENUM status
                'status'             => ['required', Rule::in(['diusulkan', 'disetujui'])]
            ]);

            $filepj = $request->file('file_pertanggungjawaban');
            $filepjname = Str::uuid() . '.' . $filepj->getClientOriginalExtension();
            $path = $filepj->storeAs('uploads', $filepjname, 'public'); // simpan ke storage/app/public/uploads

            $foto = $request->file('foto');
            $fotoname = Str::uuid() . '.' . $foto->getClientOriginalExtension();
            $path = $foto->storeAs('uploads', $fotoname, 'public'); // simpan ke storage/app/public/uploads

            // masukkan nama atau path file ke data yang akan disimpan
            $validated['file_pertanggungjawaban'] = $filepjname; // atau: Storage::url($path) jika ingin menyimpan URL publik
            $validated['foto'] = $fotoname; // atau: Storage::url($path) jika ingin menyimpan URL publik
            $id_user = Auth::check() ? Auth::user()->iduser : null;
            $validated['created_by'] = $id_user;

            $spj = Spj::create($validated);


            return response()->json([
                'code'    => 'success',
                'message' => 'SPJ berhasil dibuat',
                'data'    => $spj,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal membuat SPJ',
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
           $spj = Spj::with(['usulan'])->findOrFail($id);
           $spj->file_pertanggungjawaban = $spj->file_pertanggungjawaban ? Storage::url('uploads/' . $spj->file_pertanggungjawaban) : null;
           $spj->foto = $spj->foto ? Storage::url('uploads/' . $spj->foto) : null;
            return response()->json([
                'code'    => 'success',
                'message' => 'OK',
                'data'    => $spj,
            ], 200);
        } catch (\Throwable|ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'SPJ tidak ditemukan',
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
            $spj = Spj::findOrFail($id);
            $validated = $request->validate([
               'idusulan' => ['sometimes', 'integer', 'exists:usulan,idusulan'],

                // file rar/zip max 2MB
                'file_pertanggungjawaban'   => ['sometimes', 'file', 'max:2048'],
                'foto'   => ['sometimes', 'file', 'max:2048', 'mimes:jpg,jpeg,png'],

                'realisasi'              => ['sometimes', 'integer', 'min:0'],

                // ✅ ENUM status
                'status'             => ['sometimes', Rule::in(['diusulkan', 'disetujui'])]
            ]);

            if ($request->hasFile('file_pertanggungjawaban')) {
                // hapus file lama jika ada
                $oldFilepj = (string) $spj->file_pertanggungjawaban;
                if ($oldFilepj && Storage::exists('public/uploads/' . $oldFilepj)) {
                    Storage::delete('public/uploads/' . $oldFilepj);
                }

                // hapus file lama jika ada
                $oldFilefoto = (string) $spj->foto;
                if ($oldFilefoto && Storage::exists('public/uploads/' . $oldFilefoto)) {
                    Storage::delete('public/uploads/' . $oldFilefoto);
                }

                $file = $request->file('file_pertanggungjawaban');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads', $filename, 'public');
                $validated['file_pertanggungjawaban'] = $filename;
            }
            $id_user = Auth::check() ? Auth::user()->iduser : null;
             $validated['updated_by'] = $id_user;

            $spj->update($validated);
            return response()->json([
                'message' => 'SPJ berhasil diperbarui',
                'data'    => $spj->fresh(),
            ], 200);

        } catch (Throwable|ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal memperbarui SPJ',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id  )
    {
        try {
            $spj = Spj::findOrFail($id);
            // hapus file dari storage jika ada
            if ($spj->file_pertanggungjawaban && Storage::exists('public/uploads/' . $spj->file_pertanggungjawaban)) {
                Storage::delete('public/uploads/' . $spj->file_pertanggungjawaban);
            }

            if ($spj->foto && Storage::exists('public/uploads/' . $spj->foto)) {
                Storage::delete('public/uploads/' . $spj->foto);
            }

            $spj->delete();
            return response()->json([
                'code'    => 'success',
                'message' => 'SPJ berhasil dihapus',
            ], 200);
        } catch (Throwable|ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal menghapus SPJ',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
