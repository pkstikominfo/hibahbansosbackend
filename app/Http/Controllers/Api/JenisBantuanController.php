<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JenisBantuan;
use Throwable;


class JenisBantuanController extends Controller
{
    public function index()
    {
         try {
            $jenisBantuan = JenisBantuan::all();

            return response()->json([
                'success' => true,
                'message' => 'Data jenis bantuan berhasil diambil',
                'data' => $jenisBantuan
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
