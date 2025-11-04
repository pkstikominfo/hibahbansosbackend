<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Opd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::with('opd')->get();

            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user',
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
                'username' => 'required|string|max:10|unique:users,username',
                'password' => 'required|string|min:6',
                'name' => 'required|string|max:75',
                'email' => 'required|email|max:30|unique:users,email',
                'nohp' => 'required|string|max:12',
                'peran' => ['required', 'string', Rule::in(['admin', 'opd', 'pengusul'])],
                'kode_opd' => 'nullable|string|max:10|exists:opd,kode_opd',
                'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])]
            ], [
                'username.required' => 'Username wajib diisi',
                'username.max' => 'Username maksimal 10 karakter',
                'username.unique' => 'Username sudah digunakan',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 6 karakter',
                'name.required' => 'Nama lengkap wajib diisi',
                'name.max' => 'Nama lengkap maksimal 75 karakter',
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah digunakan',
                'nohp.required' => 'Nomor HP wajib diisi',
                'peran.required' => 'Peran wajib dipilih',
                'peran.in' => 'Peran harus admin, opd, atau pengusul',
                'kode_opd.exists' => 'OPD tidak valid',
                'status.in' => 'Status harus active atau inactive'
            ]);

            // Validasi tambahan: kode_opd wajib jika peran adalah opd
            $validator->after(function ($validator) use ($request) {
                if ($request->peran === 'opd' && empty($request->kode_opd)) {
                    $validator->errors()->add('kode_opd', 'Kode OPD wajib diisi untuk peran OPD');
                }

                if ($request->peran !== 'opd' && !empty($request->kode_opd)) {
                    $validator->errors()->add('kode_opd', 'Kode OPD hanya untuk peran OPD');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create($validator->validated());
            $user->load('opd');

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dibuat',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user',
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
            $user = User::with('opd')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user',
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
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'username' => 'sometimes|required|string|max:10|unique:users,username,' . $id . ',id',
                'password' => 'sometimes|required|string|min:6',
                'name' => 'sometimes|required|string|max:75',
                'email' => 'sometimes|required|email|max:30|unique:users,email,' . $id . ',id',
                'nohp' => 'sometimes|required|string|max:12',
                'peran' => ['sometimes', 'required', 'string', Rule::in(['admin', 'opd', 'pengusul'])],
                'kode_opd' => 'nullable|string|max:10|exists:opd,kode_opd',
                'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive'])]
            ], [
                'username.required' => 'Username wajib diisi',
                'username.max' => 'Username maksimal 10 karakter',
                'username.unique' => 'Username sudah digunakan',
                'password.min' => 'Password minimal 6 karakter',
                'name.required' => 'Nama lengkap wajib diisi',
                'name.max' => 'Nama lengkap maksimal 75 karakter',
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah digunakan',
                'nohp.required' => 'Nomor HP wajib diisi',
                'peran.required' => 'Peran wajib dipilih',
                'peran.in' => 'Peran harus admin, opd, atau pengusul',
                'kode_opd.exists' => 'OPD tidak valid',
                'status.in' => 'Status harus active atau inactive'
            ]);

            // Validasi tambahan: kode_opd wajib jika peran adalah opd
            $validator->after(function ($validator) use ($request, $user) {
                if ($request->has('peran') && $request->peran === 'opd' && empty($request->kode_opd)) {
                    $validator->errors()->add('kode_opd', 'Kode OPD wajib diisi untuk peran OPD');
                }

                if ($request->has('peran') && $request->peran !== 'opd' && !empty($request->kode_opd)) {
                    $validator->errors()->add('kode_opd', 'Kode OPD hanya untuk peran OPD');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update($validator->validated());
            $user->load('opd');

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diperbarui',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui user',
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
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Cek apakah user memiliki usulan log
            if ($user->usulanLogs()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus user karena masih memiliki riwayat usulan'
                ], 422);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users by name, username, or email
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'keyword' => 'required|string|min:2'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $keyword = $validator->validated()['keyword'];
            $users = User::cari($keyword)
                ->with('opd')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Pencarian user berhasil',
                'data' => $users,
                'search_term' => $keyword
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role): JsonResponse
    {
        try {
            if (!in_array($role, ['admin', 'opd', 'pengusul'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Peran tidak valid. Pilih: admin, opd, atau pengusul'
                ], 422);
            }

            // Ganti scope dengan where biasa
            $users = User::where('peran', $role)
                ->with('opd')
                ->get();

            return response()->json([
                'success' => true,
                'message' => "Data user dengan peran {$role} berhasil diambil",
                'data' => $users,
                'total' => $users->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update user status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => ['required', 'string', Rule::in(['active', 'inactive'])]
            ], [
                'status.required' => 'Status wajib diisi',
                'status.in' => 'Status harus active atau inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update(['status' => $validator->validated()['status']]);
            $user->load('opd');

            return response()->json([
                'success' => true,
                'message' => 'Status user berhasil diperbarui',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users with pagination
     */
    public function paginated(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $users = User::with('opd')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
