<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsulanPersyaratan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UsulanPersyaratanController extends Controller
{
    /**
     * STORE
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'idusulan' => 'required|exists:usulan,idusulan',
                'id_fp' => 'required|exists:file_persyaratan,id_fp',
                'file_persyaratan' => 'required|file|mimes:pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // 🔐 NAMA FILE AMAN
            $filename = time() . '.' . $request->file('file_persyaratan')->getClientOriginalExtension();

            // ⬆️ SIMPAN KE public/uploads
            $request->file('file_persyaratan')
                ->move(public_path('uploads'), $filename);

            $data = UsulanPersyaratan::create([
                'idusulan' => $request->idusulan,
                'id_fp' => $request->id_fp,
                'file_persyaratan' => $filename,
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'file_url' => asset('uploads/' . $filename)
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE FILE SAJA
     */
    public function update(Request $request, $id)
    {
        try {
            $data = UsulanPersyaratan::findOrFail($id);

            $request->validate([
                'file_persyaratan' => 'required|file|mimes:pdf|max:2048',
            ]);

            // 🧹 HAPUS FILE LAMA
            if ($data->file_persyaratan && file_exists(public_path('uploads/' . $data->file_persyaratan))) {
                unlink(public_path('uploads/' . $data->file_persyaratan));
            }

            // 🔐 FILE BARU
            $filename = time() . '.' . $request->file('file_persyaratan')->getClientOriginalExtension();

            $request->file('file_persyaratan')
                ->move(public_path('uploads'), $filename);

            $data->update([
                'file_persyaratan' => $filename
            ]);

            return response()->json([
                'success' => true,
                'file_url' => asset('uploads/' . $filename),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE
     */
    public function destroy($id)
    {
        try {
            $data = UsulanPersyaratan::findOrFail($id);

            if ($data->file_persyaratan && file_exists(public_path('uploads/' . $data->file_persyaratan))) {
                unlink(public_path('uploads/' . $data->file_persyaratan));
            }

            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data & file berhasil dihapus'
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DOWNLOAD (AMAN)
     */
    public function download($id)
    {
        $data = UsulanPersyaratan::findOrFail($id);
        $path = public_path('uploads/' . $data->file_persyaratan);

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return response()->download($path);
    }
}
