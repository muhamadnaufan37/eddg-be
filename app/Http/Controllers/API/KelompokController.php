<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use Illuminate\Http\Request;

class KelompokController extends Controller
{
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = dataKelompok::select([
            'tabel_kelompok.id',
            'tabel_kelompok.nama_kelompok',
            'tabel_desa.nama_desa AS parent_desa',
            'tabel_kelompok.desa_id',
        ])
        ->leftJoin('tabel_desa', 'tabel_kelompok.desa_id', '=', 'tabel_desa.id');

        if (!empty($keyword)) {
            $table_kelompok = $model->where('tabel_kelompok.nama_kelompok', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('tabel_kelompok.id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_kelompok = $model->paginate($perPage);
        }

        $table_kelompok->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_kelompok' => $table_kelompok,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $tabel_desa = dataDesa::find($request->desa_id);

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
        ];

        $request->validate([
            'nama_kelompok' => 'required|max:225|unique:tabel_kelompok',
            'desa_id' => 'required|numeric',
        ], $customMessages);

        $table_kelompok = new dataKelompok();
        $table_kelompok->nama_kelompok = ucwords(strtolower($request->nama_kelompok));
        $table_kelompok->desa_id = $request->desa_id;
        try {
            if (!$tabel_desa) {
                return response()->json([
                    'message' => 'Desa dengan ID yang diberikan tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            $table_kelompok->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Kelompok'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_kelompok->created_at, $table_kelompok->updated_at);

        return response()->json([
            'message' => 'Data Kelompok berhasil ditambahkan',
            'data_kelompok' => $table_kelompok,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_kelompok = dataKelompok::where('id', '=', $request->id)->first();

        unset($table_kelompok->created_at, $table_kelompok->updated_at);

        if (!empty($table_kelompok)) {
            return response()->json([
                'message' => 'Sukses',
                'data_kelompok' => $table_kelompok,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Kelompok tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update(Request $request)
    {
        $tabel_desa = dataDesa::find($request->desa_id);

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
        ];

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'nama_kelompok' => 'required|max:225',
            'desa_id' => 'required|numeric',
        ], $customMessages);

        $table_kelompok = dataKelompok::where('id', '=', $request->id)->first();

        if (!empty($table_kelompok)) {
            try {
                if (!$tabel_desa) {
                    return response()->json([
                        'message' => 'Desa dengan ID yang diberikan tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                $table_kelompok->update([
                    'id' => $request->id,
                    'nama_kelompok' => ucwords(strtolower($request->nama_kelompok)),
                    'desa_id' => $request->desa_id,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Kelompok'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Kelompok berhasil diupdate',
                'data_kelompok' => $table_kelompok,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Kelompok tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_kelompok = dataKelompok::where('id', '=', $request->id)
            ->first();

        if (!empty($table_kelompok)) {
            try {
                $table_kelompok = dataKelompok::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Kelompok berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data Kelompok'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Kelompok tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
