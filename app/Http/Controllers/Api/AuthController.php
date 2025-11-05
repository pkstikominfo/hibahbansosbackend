<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:20|unique:users,username',
                'password' => 'required|string|min:6|confirmed',
                'name' => 'required|string|max:75',
                'email' => 'required|email|max:30|unique:users,email',
                'nohp' => 'required|string|max:12',
                'peran' => 'required|string|in:opd,pengusul', // Admin hanya bisa dibuat manual
            ], [
                'username.required' => 'Username wajib diisi',
                'username.unique' => 'Username sudah digunakan',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 6 karakter',
                'password.confirmed' => 'Konfirmasi password tidak sesuai',
                'name.required' => 'Nama lengkap wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.unique' => 'Email sudah digunakan',
                'nohp.required' => 'Nomor HP wajib diisi',
                'peran.required' => 'Peran wajib dipilih',
                'peran.in' => 'Peran harus opd atau pengusul'
            ]);

            // Validasi tambahan untuk OPD
            $validator->after(function ($validator) use ($request) {
                if ($request->peran === 'opd' && empty($request->kode_opd)) {
                    $validator->errors()->add('kode_opd', 'Kode OPD wajib diisi untuk peran OPD');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'email' => $request->email,
                'nohp' => $request->nohp,
                'peran' => $request->peran,
                'kode_opd' => $request->kode_opd,
                'status' => 'active' // Auto activate for now
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan registrasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
                'password' => 'required|string'
            ], [
                'username.required' => 'Username wajib diisi',
                'password.required' => 'Password wajib diisi'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cari user by username atau email
            $user = User::where('username', $request->username)
                ->orWhere('email', $request->username)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username/Email atau password salah'
                ], 401);
            }

            if ($user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'
                ], 401);
            }

            // Hapus token lama (optional)
            $user->tokens()->delete();

            // Buat token baru
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Hapus token yang sedang digunakan
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('opd');

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
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:75',
                'email' => 'sometimes|required|email|max:30|unique:users,email,' . $user->id,
                'nohp' => 'sometimes|required|string|max:12',
            ], [
                'name.required' => 'Nama lengkap wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.unique' => 'Email sudah digunakan',
                'nohp.required' => 'Nomor HP wajib diisi'
            ]);

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
                'message' => 'Profil berhasil diperbarui',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ], [
                'current_password.required' => 'Password saat ini wajib diisi',
                'new_password.required' => 'Password baru wajib diisi',
                'new_password.min' => 'Password baru minimal 6 karakter',
                'new_password.confirmed' => 'Konfirmasi password baru tidak sesuai'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini salah'
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Hapus semua token (force logout dari semua device)
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah. Silakan login kembali.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
