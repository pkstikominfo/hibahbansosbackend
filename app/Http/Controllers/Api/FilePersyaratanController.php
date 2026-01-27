<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FilePersyaratan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class FilePersyaratanController extends Controller
{
    /**
     * GET: tampilkan semua data
     */
    public function index()
    {
        try {
            $data = FilePersyaratan::with(['opd', 'subJenisBantuan'])->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: simpan data baru
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_opd' => 'required|exists:opd,kode_opd',
                'nama_persyaratan' => 'required|string|max:100',
                'idsubjenisbantuan' => 'required|exists:sub_jenis_bantuan,idsubjenisbantuan',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = FilePersyaratan::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $data
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: detail data
     */
    public function show($id)
    {
        try {
            $data = FilePersyaratan::with(['opd', 'subJenisBantuan'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * PUT/PATCH: update data
     */
    public function update(Request $request, $id)
    {
        try {
            $data = FilePersyaratan::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'id_opd' => 'required|exists:opd,kode_opd',
                'nama_persyaratan' => 'required|string|max:100',
                'idsubjenisbantuan' => 'required|exists:sub_jenis_bantuan,idsubjenisbantuan',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $data
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat update data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE: hapus data
     */
    public function destroy($id)
    {
        try {
            $data = FilePersyaratan::findOrFail($id);
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   public function getByOpd(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'id_opd' => 'required|exists:opd,kode_opd',
            'idsubjenisbantuan' => 'required|exists:sub_jenis_bantuan,idsubjenisbantuan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = FilePersyaratan::with(['opd', 'subJenisBantuan'])
            ->where('id_opd', $request->id_opd)
            ->where('idsubjenisbantuan', $request->idsubjenisbantuan)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);

    } catch (Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data',
            'error' => $e->getMessage()
        ], 500);
    }
}

 public function getByLoginOpd(Request $request)
    {
        try {
            $user = $request->user();

            // ❌ user login tidak punya kode_opd
            if (!$user->kode_opd) {
                return response()->json([
                    'success' => false,
                    'message' => 'User login tidak memiliki kode OPD'
                ], 403);
            }

            $query = FilePersyaratan::with(['opd', 'subJenisBantuan'])
                ->where('id_opd', $user->kode_opd);

            // 🔍 OPTIONAL FILTER
            if ($request->filled('idsubjenisbantuan')) {
                $query->where(
                    'idsubjenisbantuan',
                    $request->input('idsubjenisbantuan')
                );
            }

            $data = $query->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil file persyaratan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}


