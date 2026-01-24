<?php

namespace App\Http\Controllers\Api;
use App\Models\Kategori;
use Throwable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class KategoriController extends Controller
{
    /**
     * GET: tampilkan semua data kategori
     */
    public function index()
    {
         try {
            $kategori = Kategori::with(['jenisBantuan'])->all();
            return response()->json([
                'success' => true,
                'message' => 'Data kategori berhasil diambil',
                'data' => $kategori
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     public function getByJenisBantuan(String $id_jenisbantuan)
    {
        try {
            $kategori = Kategori::with(['jenisBantuan'])->where('idjenisbantuan', $id_jenisbantuan)->get();

            // ✅ Authorization check


            return response()->json([
                'code'    => 'success',
                'message' => 'OK',
                'data'    => $kategori,
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized: Anda tidak memiliki akses ke kategori ini',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Kategori tidak ditemukan',
                'error'   => $e->getMessage(),
            ], status: 404);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal mengambil data kategori',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
