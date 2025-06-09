<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use Illuminate\Http\Request;

class MappingTempatSambungController extends Controller
{
    public function list_data_desa(Request $request)
    {
        $customMessages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'min' => ':attribute harus memiliki setidaknya :min karakter.',
            'regex' => ':attribute harus mengandung setidaknya satu huruf kapital dan satu angka.',
            'numeric' => ':attribute harus berupa angka.',
            'digits_between' => ':attribute harus memiliki panjang antara :min dan :max digit.',
            'exists' => ':attribute yang dipilih tidak valid',
        ];

        $request->validate([
            'daerah_id' => 'required|numeric|exists:tabel_daerah,id',
        ], $customMessages);

        $mapping_tempat_sambung = dataDesa::where('daerah_id', $request->daerah_id)
            ->select('id', 'nama_desa') // Selecting both id and nama_desa columns
            ->orderBy('nama_desa')
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_tempat_sambung' => $mapping_tempat_sambung,
            'success' => true,
        ], 200);
    }

    public function list_data_kelompok(Request $request)
    {
        $customMessages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'min' => ':attribute harus memiliki setidaknya :min karakter.',
            'regex' => ':attribute harus mengandung setidaknya satu huruf kapital dan satu angka.',
            'numeric' => ':attribute harus berupa angka.',
            'digits_between' => ':attribute harus memiliki panjang antara :min dan :max digit.',
            'exists' => ':attribute yang dipilih tidak valid',
        ];

        $request->validate([
            'desa_id' => 'required|numeric|exists:tabel_desa,id',
        ], $customMessages);

        $mapping_tempat_sambung = dataKelompok::where('desa_id', $request->desa_id)
            ->select('id', 'nama_kelompok')
            ->orderBy('nama_kelompok')
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_tempat_sambung' => $mapping_tempat_sambung,
            'success' => true,
        ], 200);
    }
}
