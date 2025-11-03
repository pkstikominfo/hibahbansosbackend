<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KecamatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $kecamatans = Kecamatan::all();

            return response()->json([
                'success' => true,
                'message' => 'Data kecamatan berhasil diambil',
                'data' => $kecamatans
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kecamatan',
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
                // HAPUS validasi untuk idkecamatan karena auto increment
                'namakecamatan' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create kecamatan tanpa menyertakan idkecamatan
            $kecamatan = Kecamatan::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Kecamatan berhasil dibuat',
                'data' => $kecamatan
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat kecamatan',
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
            $kecamatan = Kecamatan::find($id);

            if (!$kecamatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data kecamatan berhasil diambil',
                'data' => $kecamatan
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kecamatan',
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
            $kecamatan = Kecamatan::find($id);

            if (!$kecamatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'namakecamatan' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $kecamatan->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Kecamatan berhasil diperbarui',
                'data' => $kecamatan
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui kecamatan',
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
            $kecamatan = Kecamatan::find($id);

            if (!$kecamatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak ditemukan'
                ], 404);
            }

            // Cek apakah kecamatan memiliki desa
            if ($kecamatan->desas()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus kecamatan karena masih memiliki desa'
                ], 422);
            }

            $kecamatan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kecamatan berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kecamatan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search kecamatan by name
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

            $kecamatans = Kecamatan::cariNama($validator->validated()['nama'])->get();

            return response()->json([
                'success' => true,
                'message' => 'Pencarian kecamatan berhasil',
                'data' => $kecamatans
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari kecamatan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
