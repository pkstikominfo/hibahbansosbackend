<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpjPersyaratan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SpjPersyaratanController extends Controller
{
    /**
     * GET: semua data
     */
    public function index()
    {
        try {
            $data = SpjPersyaratan::with('spj')->get();

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
     * POST: simpan data + upload file
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'idspj' => 'required|exists:spj,idspj',
                'file_persyaratan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file_persyaratan');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('uploads', $filename);

            $data = SpjPersyaratan::create([
                'idspj' => $request->idspj,
                'file_persyaratan' => $filename,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data SPJ persyaratan berhasil disimpan',
                'data' => $data
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: detail data
     */
    public function show($id)
    {
        try {
            $data = SpjPersyaratan::with('spj')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $data,
                'file_url' => asset('storage/uploads/' . $data->file_persyaratan)
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getByIdSpj($id)
    {
        try {
            $data = SpjPersyaratan::with('spj')->where('idspj', $id)->get();

            $data->transform(function ($item) {
                $item->file_url = asset('storage/uploads/' . $item->file_persyaratan);
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * PUT/PATCH: update data + optional upload file
     */
    public function update(Request $request, $id)
    {
        try {
            $data = SpjPersyaratan::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'idspj' => 'required|exists:spj,idspj',
                'file_persyaratan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('file_persyaratan')) {

                if ($data->file_persyaratan && Storage::exists('uploads/' . $data->file_persyaratan)) {
                    Storage::delete('uploads/' . $data->file_persyaratan);
                }

                $file = $request->file('file_persyaratan');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('uploads', $filename);

                $data->file_persyaratan = $filename;
            }

            $data->idspj = $request->idspj;
            $data->save();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $data
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat update data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE: hapus data + file
     */
    public function destroy($id)
    {
        try {
            $data = SpjPersyaratan::findOrFail($id);

            if ($data->file_persyaratan && Storage::exists('uploads/' . $data->file_persyaratan)) {
                Storage::delete('uploads/' . $data->file_persyaratan);
            }

            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data dan file berhasil dihapus'
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
     * DOWNLOAD FILE
     */
    public function download($id)
    {
        try {
            $data = SpjPersyaratan::findOrFail($id);
            $path = 'uploads/' . $data->file_persyaratan;

            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            return Storage::download($path);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendownload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
