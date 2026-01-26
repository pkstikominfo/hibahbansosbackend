<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OpdController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $opds = Opd::all();

            return response()->json([
                'success' => true,
                'message' => 'Data OPD berhasil diambil',
                'data' => $opds
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'kode_opd' => 'required|string|max:10|unique:opd,kode_opd',
                'nama_opd' => 'required|string|max:255'
            ], [
                'kode_opd.required' => 'Kode OPD wajib diisi',
                'kode_opd.max' => 'Kode OPD maksimal 10 karakter',
                'kode_opd.unique' => 'Kode OPD sudah digunakan',
                'nama_opd.required' => 'Nama OPD wajib diisi',
                'nama_opd.max' => 'Nama OPD maksimal 255 karakter'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $opd = Opd::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'OPD berhasil dibuat',
                'data' => $opd
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $opd = Opd::find($id);

            if (!$opd) {
                return response()->json([
                    'success' => false,
                    'message' => 'OPD tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data OPD berhasil diambil',
                'data' => $opd
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $opd = Opd::find($id);

            if (!$opd) {
                return response()->json([
                    'success' => false,
                    'message' => 'OPD tidak ditemukan'
                ], 404);
            }

            $this->authorize('update', $opd);

            $validator = Validator::make($request->all(), [
                'kode_opd' => 'sometimes|required|string|max:10|unique:opd,kode_opd,' . $id . ',kode_opd',
                'nama_opd' => 'sometimes|required|string|max:255'
            ], [
                'kode_opd.required' => 'Kode OPD wajib diisi',
                'kode_opd.max' => 'Kode OPD maksimal 10 karakter',
                'kode_opd.unique' => 'Kode OPD sudah digunakan',
                'nama_opd.required' => 'Nama OPD wajib diisi',
                'nama_opd.max' => 'Nama OPD maksimal 255 karakter'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $opd->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'OPD berhasil diperbarui',
                'data' => $opd
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Custom error message jika user nekat edit punya orang lain
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Anda hanya boleh mengedit data OPD Anda sendiri.'
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $opd = Opd::find($id);

            if (!$opd) {
                return response()->json([
                    'success' => false,
                    'message' => 'OPD tidak ditemukan'
                ], 404);
            }

            // Cek apakah OPD memiliki user
            if ($opd->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus OPD karena masih memiliki user'
                ], 422);
            }

            // Cek apakah OPD memiliki usulan
            if ($opd->usulans()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus OPD karena masih memiliki usulan'
                ], 422);
            }

            $opd->delete();

            return response()->json([
                'success' => true,
                'message' => 'OPD berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search OPD by name
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|min:2'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $nama = $validator->validated()['nama'];
            $opds = Opd::cariNama($nama)->get();

            return response()->json([
                'success' => true,
                'message' => 'Pencarian OPD berhasil',
                'data' => $opds,
                'search_term' => $nama
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OPD with users count
     */
    public function withUsersCount(): JsonResponse
    {
        try {
            $opds = Opd::withCount('users')->get();

            return response()->json([
                'success' => true,
                'message' => 'Data OPD dengan jumlah user berhasil diambil',
                'data' => $opds
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OPD with pagination
     */
    public function paginated(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $opds = Opd::paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data OPD berhasil diambil',
                'data' => $opds
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data OPD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        // 1. Ambil user yang sedang login
        $user = $request->user();

        // 2. Cek apakah user memiliki kode_opd
        // (Bisa juga ditambahkan pengecekan role jika ingin strict: $user->peran !== 'opd')
        if (!$user->kode_opd) {
            return response()->json([
                'success' => false,
                'message' => 'User ini tidak terhubung dengan data OPD manapun.',
            ], 404);
        }

        // 3. Cari data OPD berdasarkan kode_opd milik user
        $opd = Opd::where('kode_opd', $user->kode_opd)->first();

        if (!$opd) {
            return response()->json([
                'success' => false,
                'message' => 'Data OPD tidak ditemukan.',
            ], 404);
        }

        // 4. Return data
        return response()->json([
            'success' => true,
            'message' => 'Data OPD user yang sedang login berhasil diambil',
            'data'    => $opd
        ], 200);
    }
}
