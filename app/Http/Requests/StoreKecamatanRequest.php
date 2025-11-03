<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKecamatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idkecamatan' => 'required|integer|unique:kecamatan,idkecamatan',
            'namakecamatan' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'idkecamatan.required' => 'ID Kecamatan wajib diisi',
            'idkecamatan.unique' => 'ID Kecamatan sudah digunakan',
            'namakecamatan.required' => 'Nama kecamatan wajib diisi',
            'namakecamatan.max' => 'Nama kecamatan maksimal 255 karakter'
        ];
    }
}
