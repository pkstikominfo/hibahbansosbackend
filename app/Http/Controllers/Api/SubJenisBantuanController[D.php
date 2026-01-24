<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;
use App\Models\SubJenisBantuan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubJenisBantuanController extends Controller
{
    public function index()
    {
         try {
            $subJenisBantuan = SubJenisBantuan::all();

            return response()->json([
                'success' => true,
                'message' => 'Data sub jenis bantuan berhasil diambil',
                'data' => $subJenisBantuan
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
            $subjenisbantuan = SubJenisBantuan::with(['jenisBantuan'])->where('idjenisbantuan', $id_jenisbantuan)->get();

            // ✅ Authorization check


            return response()->json([
                'code'    => 'success',
                'message' => 'OK',
                'data'    => $subjenisbantuan,
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Unauthorized: Anda tidak memiliki akses ke Subjenis Bantuan ini',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Subjenis Bantuan tidak ditemukan',
                'error'   => $e->getMessage(),
            ], status: 404);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => 'error',
                'message' => 'Gagal mengambil data sub jenis bantuan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
