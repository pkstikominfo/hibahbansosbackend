<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DesaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $desas = Desa::with('kecamatan')->get();

            return response()->json([
                'success' => true,
                'message' => 'Data desa berhasil diambil',
                'data' => $desas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa',
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
                'idkecamatan' => 'required|integer|exists:kecamatan,idkecamatan',
                'namadesa' => 'required|string|max:255|unique:desa,namadesa',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180'
            ], [
                'idkecamatan.required' => 'Kecamatan wajib dipilih',
                'idkecamatan.exists' => 'Kecamatan tidak valid',
                'namadesa.required' => 'Nama desa wajib diisi',
                'namadesa.max' => 'Nama desa maksimal 255 karakter',
                'namadesa.unique' => 'Nama desa sudah digunakan',
                'latitude.between' => 'Latitude harus antara -90 dan 90',
                'longitude.between' => 'Longitude harus antara -180 dan 180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $desa = Desa::create($validator->validated());
            $desa->load('kecamatan');

            return response()->json([
                'success' => true,
                'message' => 'Desa berhasil dibuat',
                'data' => $desa
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat desa',
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
            $desa = Desa::with('kecamatan')->find($id);

            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data desa berhasil diambil',
                'data' => $desa
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa',
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
            $desa = Desa::find($id);

            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'idkecamatan' => 'sometimes|required|integer|exists:kecamatan,idkecamatan',
                'namadesa' => 'sometimes|required|string|max:255|unique:desa,namadesa,' . $id . ',iddesa',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180'
            ], [
                'idkecamatan.exists' => 'Kecamatan tidak valid',
                'namadesa.required' => 'Nama desa wajib diisi',
                'namadesa.max' => 'Nama desa maksimal 255 karakter',
                'namadesa.unique' => 'Nama desa sudah digunakan',
                'latitude.between' => 'Latitude harus antara -90 dan 90',
                'longitude.between' => 'Longitude harus antara -180 dan 180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $desa->update($validator->validated());
            $desa->load('kecamatan');

            return response()->json([
                'success' => true,
                'message' => 'Desa berhasil diperbarui',
                'data' => $desa
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui desa',
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
            $desa = Desa::find($id);

            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            // Cek apakah desa memiliki usulan (jika ada relasi)
            if (method_exists($desa, 'usulans') && $desa->usulans()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus desa karena masih memiliki usulan'
                ], 422);
            }

            $desa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Desa berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get desa by kecamatan
     */
    public function getByKecamatan(string $idKecamatan): JsonResponse
    {
        try {
            $kecamatan = Kecamatan::find($idKecamatan);

            if (!$kecamatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak ditemukan'
                ], 404);
            }

            $desas = Desa::where('idkecamatan', $idKecamatan)
                ->with('kecamatan')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data desa berdasarkan kecamatan berhasil diambil',
                'data' => $desas,
                'kecamatan' => $kecamatan
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search desa by name
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
            $desas = Desa::cariNama($nama)
                ->with('kecamatan')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Pencarian desa berhasil',
                'data' => $desas,
                'search_term' => $nama
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all desa with pagination
     */
    public function paginated(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $desas = Desa::with('kecamatan')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data desa berhasil diambil',
                'data' => $desas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get desa berdasarkan koordinat (radius search)
     */
    public function getByCoordinates(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|numeric|min:1|max:100' // dalam kilometer
            ], [
                'latitude.required' => 'Latitude wajib diisi',
                'latitude.between' => 'Latitude harus antara -90 dan 90',
                'longitude.required' => 'Longitude wajib diisi',
                'longitude.between' => 'Longitude harus antara -180 dan 180',
                'radius.min' => 'Radius minimal 1 km',
                'radius.max' => 'Radius maksimal 100 km'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $latitude = $validated['latitude'];
            $longitude = $validated['longitude'];
            $radius = $validated['radius'] ?? 10; // default 10 km

            $desas = Desa::dekatDengan($latitude, $longitude, $radius)
                ->with('kecamatan')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data desa berdasarkan koordinat berhasil diambil',
                'data' => $desas,
                'search_params' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius_km' => $radius
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa berdasarkan koordinat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get desa yang memiliki koordinat
     */
    public function denganKoordinat(): JsonResponse
    {
        try {
            $desas = Desa::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with('kecamatan')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data desa dengan koordinat berhasil diambil',
                'data' => $desas,
                'total' => $desas->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa dengan koordinat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update koordinat untuk desa tertentu
     */
    public function updateKoordinat(Request $request, string $id): JsonResponse
    {
        try {
            $desa = Desa::find($id);

            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ], [
                'latitude.required' => 'Latitude wajib diisi',
                'latitude.between' => 'Latitude harus antara -90 dan 90',
                'longitude.required' => 'Longitude wajib diisi',
                'longitude.between' => 'Longitude harus antara -180 dan 180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $desa->update($validator->validated());
            $desa->load('kecamatan');

            return response()->json([
                'success' => true,
                'message' => 'Koordinat desa berhasil diperbarui',
                'data' => $desa
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui koordinat desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
