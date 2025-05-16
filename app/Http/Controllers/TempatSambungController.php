<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\User;
use Illuminate\Http\Request;

class TempatSambungController extends Controller
{

    public function list_all_daerah(Request $request)
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

        // Tangani permintaan kosong
        if (!$request->all()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dikirimkan.',
            ], 400);
        }

        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
        ], $customMessages);

        // Cek role_id
        $operator = User::where('uuid', $request->id_operator)->first();

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin.',
            ], 403);
        }

        // Get data data_tempat_sambung
        $data_tempat_sambung = dataDaerah::select(['id', 'nama_daerah'])
            ->groupBy('id', 'nama_daerah')->orderBy('nama_daerah')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_tempat_sambung' => $data_tempat_sambung,
            'success' => true,
        ], 200);
    }

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

        // Tangani permintaan kosong
        if (!$request->all()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dikirimkan.',
            ], 400);
        }

        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
            'daerah_id' => 'required|numeric|exists:tabel_daerah,id',
        ], $customMessages);

        // Cek role_id
        $operator = User::where('uuid', $request->id_operator)->first();

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin.',
            ], 403);
        }

        $mapping_tempat_sambung = dataDesa::where('daerah_id', $request->daerah_id)
            ->select('id', 'nama_desa')
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

        // Tangani permintaan kosong
        if (!$request->all()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dikirimkan.',
            ], 400);
        }

        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
            'desa_id' => 'required|numeric|exists:tabel_desa,id',
        ], $customMessages);

        // Cek role_id
        $operator = User::where('uuid', $request->id_operator)->first();

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin.',
            ], 403);
        }

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
