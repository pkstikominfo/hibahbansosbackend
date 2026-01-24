<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TokenController extends Controller
{
    /**
     * GET: semua data token
     */
    public function index()
    {
        try {
            $data = Token::all();

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
     * POST: simpan token baru
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'source' => 'required|string|max:50',
                'token'  => 'required|string|max:250',
                'nama'   => 'required|string|max:70',
                'status' => 'nullable|in:active,nonactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $data = Token::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Token berhasil disimpan',
                'data' => $data
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: detail token
     */
    public function show($id)
    {
        try {
            $data = Token::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * PUT/PATCH: update token
     */
    public function update(Request $request, $id)
    {
        try {
            $data = Token::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'source' => 'required|string|max:50',
                'token'  => 'required|string|max:250',
                'nama'   => 'required|string|max:70',
                'status' => 'required|in:active,nonactive',
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
                'message' => 'Token berhasil diupdate',
                'data' => $data
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat update token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE: hapus token
     */
    public function destroy($id)
    {
        try {
            $data = Token::findOrFail($id);
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Token berhasil dihapus'
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
