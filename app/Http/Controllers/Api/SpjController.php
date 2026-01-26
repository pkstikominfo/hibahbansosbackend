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
use Illuminate\Support\Facades\DB;


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
                ->orWhere('created_at', 'like', "%{$search}%")
                ->whereHas('usulan', fn($qq) => $qq->where('judul', 'like', "%{$search}%"));
            });
        }

        // 🔽 Sorting
        $sortBy  = $request->input('sort_by', 'idspsj');
        $sortDir = $request->input('sort_dir', 'asc');

        // kolom langsung di tabel usulan
        $directSorts = [
            'idspj', 'realisasi', 'created_at'
        ];

        // mapping sort relasi => [table, column, local_key, foreign_key]
        $relationSorts = [
            // kamu bisa ganti 'sub_jenis_bantuan' sesuai nama tabel aslinya
            'judul' => ['table' => 'usulan', 'column' => 'judul', 'local_key' => 'idusulan', 'foreign_key' => 'idusulan']];

        if (in_array($sortBy, $directSorts)) {
            $query->orderBy("spj.$sortBy", $sortDir);
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

        $spj = $query->paginate($perPage, ['*'], 'page', $page);


        // 🖼️ absolute URL untuk file
        $spj->getCollection()->transform(function ($item) {
            $item->foto = $item->foto
                ? asset("storage/uploads/{$item->foto}")
                : null;
            return $item;
        });

        return response()->json([
            'data' => $spj->items(),
            'meta' => [
                'page'        => $spj->currentPage(),
                'per_page'    => $spj->perPage(),
                'total'       => $spj->total(),
                'total_pages' => $spj->lastPage(),
                'sort_by'     => $sortBy,
                'sort_dir'    => $sortDir,
                'search'      => $search ?? null,
            ],
        ]);
    }

    public function detailBantuan(Request $request)
    {
        $query = Spj::query()
            ->select(
                'spj.idspj',
                'spj.realisasi',
                'spj.foto',
                'spj.created_at',
                DB::raw('(SELECT nama FROM usulan WHERE usulan.idusulan = spj.idusulan LIMIT 1) as nama_usulan')
            );

        // 🔍 Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('spj.idspj', 'like', "%{$search}%")
                    ->orWhere('spj.created_at', 'like', "%{$search}%")
                    ->orWhere(DB::raw('(SELECT nama FROM usulan WHERE usulan.idusulan = spj.idusulan LIMIT 1)'), 'like', "%{$search}%");
            });
        }

        // 🔽 Sorting
        $sortBy  = $request->input('sort_by', 'idspj');
        $sortDir = $request->input('sort_dir', 'asc');

        if (in_array($sortBy, ['idspj', 'realisasi', 'created_at'])) {
            $query->orderBy("spj.$sortBy", $sortDir);
        } elseif ($sortBy === 'nama') {
            $query->orderBy(DB::raw('(SELECT nama FROM usulan WHERE usulan.idusulan = spj.idusulan LIMIT 1)'), $sortDir);
        }



        // 📄 Pagination
        $perPage = (int) $request->input('per_page', 10);
        $page    = (int) $request->input('page', 1);

        $spj = $query->paginate($perPage, ['*'], 'page', $page);

           // 🖼️ absolute URL untuk file
        $spj->getCollection()->transform(function ($item) {
            $item->foto = $item->foto
                ? asset("storage/uploads/{$item->foto}")
                : null;
            return $item;
        });

        return response()->json([
            'data' => $spj->items(),
            'meta' => [
                'page'        => $spj->currentPage(),
                'per_page'    => $spj->perPage(),
                'total'       => $spj->total(),
                'total_pages' => $spj->lastPage(),
                'sort_by'     => $sortBy,
                'sort_dir'    => $sortDir,
                'search'      => $search ?? null,
            ],
        ]);
    }


    public function feedBantuan(Request $request)
    {
        $query = Spj::query()
    ->select(
        'spj.idspj',
        'spj.foto',
        'spj.created_at',
        'usulan.judul as judul_usulan',
        'usulan.anggaran_disetujui',
        'spj.realisasi',
        'opd.nama_opd as nama_opd',
        'sub_jenis_bantuan.namasubjenis as sub_jenis_bantuan',
        'kategori.namakategori as kategori',
        'desa.namadesa as nama_desa',
        'usulan.nama as nama_pengusul'
    )
    ->leftJoin('usulan', 'spj.idusulan', '=', 'usulan.idusulan')
    ->leftJoin('opd', 'usulan.kode_opd', '=', 'opd.kode_opd')
    ->leftJoin('sub_jenis_bantuan', 'usulan.idsubjenisbantuan', '=', 'sub_jenis_bantuan.idsubjenisbantuan')
    ->leftJoin('kategori', 'usulan.idkategori', '=', 'kategori.idkategori')
    ->leftJoin('desa', 'usulan.iddesa', '=', 'desa.iddesa');

// 🔎 Global search berdasarkan kolom yang di-select
if ($search = trim($request->input('q'))) {

    // daftar kolom yang dicari (pakai nama asli tabel)
    $searchable = [
        'spj.idspj',
        'spj.created_at',
        'usulan.judul',
        'usulan.anggaran_disetujui',
        'spj.realisasi',
        'opd.nama_opd',
        'sub_jenis_bantuan.namasubjenis',
        'kategori.namakategori',
        'desa.namadesa',
        'usulan.nama',
    ];

    $query->where(function ($q) use ($search, $searchable) {
            foreach ($searchable as $col) {

                // angka (kolom numeric): pakai '=' kalau input numeric, kalau bukan jatuh ke LIKE
                if (in_array($col, ['usulan.anggaran_disetujui', 'spj.realisasi'])) {
                    if (is_numeric($search)) {
                        $q->orWhere($col, '=', $search);
                    } else {
                        // fallback LIKE (misal user ketik "100." atau "1.000")
                        $q->orWhere($col, 'like', "%{$search}%");
                    }
                    continue;
                }

                // tanggal: coba cocokkan tanggal saja + LIKE untuk fleksibel
                if ($col === 'spj.created_at') {
                    $q->orWhereDate('spj.created_at', $search)
                    ->orWhere('spj.created_at', 'like', "%{$search}%");
                    continue;
                }

                // string umum
                $q->orWhere($col, 'like', "%{$search}%");
            }
        });
}

        // 🔽 Sorting
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'asc');

        // kolom langsung di tabel usulan
        $directSorts = [
             'created_at'
        ];




        if (in_array($sortBy, $directSorts)) {
            $query->orderBy("spj.$sortBy", $sortDir);
        }  else {
            // fallback aman
            $query->orderBy('idspj', 'asc');
        }

        // 📄 Pagination
        $perPage = (int) $request->input('per_page', 10);
        $page    = (int) $request->input('page', 1);

        $spj = $query->paginate($perPage, ['*'], 'page', $page);

        // 🖼️ absolute URL untuk file
        $spj->getCollection()->transform(function ($item) {
            $item->foto = $item->foto
                ? asset("storage/uploads/{$item->foto}")
                : null;
            return $item;
        });

        return response()->json([
            'data' => $spj->items(),
            'meta' => [
                'page'        => $spj->currentPage(),
                'per_page'    => $spj->perPage(),
                'total'       => $spj->total(),
                'total_pages' => $spj->lastPage(),
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
                'foto'   => ['required', 'file', 'max:2048', 'mimes:jpg,jpeg,png'],

                'realisasi'              => ['required', 'integer', 'min:0'],
            ]);


            $foto = $request->file('foto');
            $fotoname = Str::uuid() . '.' . $foto->getClientOriginalExtension();
            $path = $foto->storeAs('uploads', $fotoname, 'public'); // simpan ke storage/app/public/uploads

            // masukkan nama atau path file ke data yang akan disimpan
            $validated['foto'] = $fotoname; // atau: Storage::url($path) jika ingin menyimpan URL publik
            $id_user = Auth::check() ? Auth::user()->iduser : null;
            $validated['created_by'] = $id_user;

            $spj = Spj::create($validated);
            log_bantuan(['id_fk' => $spj->idspj]);

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

    public function getByOpd(string $kode_opd)
    {
        try {
            $spj = Spj::with('usulan')
                ->whereHas('usulan', function ($q) use ($kode_opd) {
                    $q->where('kode_opd', $kode_opd);
                })
                ->get();

            // mapping url foto
            $spj->each(function ($item) {
                $item->foto = $item->foto
                    ? Storage::url('uploads/' . $item->foto)
                    : null;
            });

            return response()->json([
                'code'    => 'success',
                'message' => 'OK',
                'data'    => $spj,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'SPJ tidak ditemukan',
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
            $spj = Spj::with('usulan')->findOrFail($id);

            $oldStatus = $spj->status;

            $validated = $request->validate([
                'idusulan'  => ['sometimes', 'integer', 'exists:usulan,idusulan'],
                'foto'      => ['sometimes', 'file', 'max:2048', 'mimes:jpg,jpeg,png'],
                'realisasi' => ['sometimes', 'integer', 'min:0'],
            ]);

            // upload foto
            if ($request->hasFile('foto')) {
                if ($spj->foto && Storage::exists('public/uploads/' . $spj->foto)) {
                    Storage::delete('public/uploads/' . $spj->foto);
                }

                $file = $request->file('foto');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads', $filename, 'public');
                $validated['foto'] = $filename;
            }

            $validated['updated_by'] = Auth::check() ? Auth::user()->iduser : null;

            $spj->update($validated);
            log_bantuan(['id_fk' => $spj->idspj]);

            // ===============================
            // 🔔 KIRIM WA JIKA STATUS BERUBAH
            // ===============================
            if (
                array_key_exists('status', $validated) &&
                $oldStatus !== $validated['status']
            ) {
                $this->sendWaStatusSpj($spj);
            }

            DB::commit();

            return response()->json([
                'message' => 'SPJ berhasil diperbarui',
                'data'    => $spj->fresh('usulan'),
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
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


            if ($spj->foto && Storage::exists('public/uploads/' . $spj->foto)) {
                Storage::delete('public/uploads/' . $spj->foto);
            }

            $spj->delete();
            log_bantuan(['id_fk' => $spj->idspj]);
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

    private function sendWaStatusSpj(Spj $spj): void
    {
        if (!$spj->usulan || !$spj->usulan->nohp) {
            return;
        }

        $no_hp = $spj->usulan->nohp;

        $cek_valid_wa = json_decode(
            validate_whatsapp(getTokenFonte(), $no_hp)
        );

        if (!$cek_valid_wa || !$cek_valid_wa->status) {
            return;
        }

        if (!empty($cek_valid_wa->not_registered)) {
            return;
        }

        $pesan = match ($spj->status) {
            'proses' =>
                "📄 *SPJ Sedang Diproses*\n\nRealisasi: Rp " .
                number_format($spj->realisasi, 0, ',', '.'),

            'selesai' =>
                "✅ *SPJ Telah Selesai*\n\nRealisasi: Rp " .
                number_format($spj->realisasi, 0, ',', '.'),

            default =>
                "📌 Status SPJ diperbarui",
        };

        send_whatsapp(
            getTokenFonte(),
            $no_hp,
            $pesan
        );
    }
}
