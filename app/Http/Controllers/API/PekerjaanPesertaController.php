<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblPekerjaan;
use Illuminate\Http\Request;

class PekerjaanPesertaController extends Controller
{
    public function list_data_all_pekerjaan()
    {
        $table_pekerjaan = tblPekerjaan::select(['id', 'nama_pekerjaan'])
            ->groupBy('id', 'nama_pekerjaan')->orderBy('nama_pekerjaan')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_pekerjaan' => $table_pekerjaan,
            'success' => true,
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblPekerjaan::select([
            'id',
            'nama_pekerjaan',
        ]);

        if (!empty($keyword)) {
            $table_pekerjaan = $model->where('nama_pekerjaan', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_pekerjaan = $model->paginate($perPage);
        }

        $table_pekerjaan->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_pekerjaan' => $table_pekerjaan,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
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
        ];

        $request->validate([
            'nama_pekerjaan' => 'required|max:225|unique:tbl_pekerjaan',
        ], $customMessages);

        $table_pekerjaan = new tblPekerjaan();
        $table_pekerjaan->nama_pekerjaan = $request->nama_pekerjaan;
        try {
            $table_pekerjaan->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data Pekerjaan'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_pekerjaan->created_at, $table_pekerjaan->updated_at);

        return response()->json([
            'message' => 'Data Pekerjaan berhasil ditambahkan',
            'data_pekerjaan' => $table_pekerjaan,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_pekerjaan = tblPekerjaan::where('id', '=', $request->id)->first();

        unset($table_pekerjaan->created_at, $table_pekerjaan->updated_at);

        if (!empty($table_pekerjaan)) {
            return response()->json([
                'message' => 'Sukses',
                'data_pekerjaan' => $table_pekerjaan,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pekerjaan tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update(Request $request)
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
        ];

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'nama_pekerjaan' => 'sometimes|required|max:225|string|unique:tbl_pekerjaan,nama_pekerjaan,'.$request->id.',id',
        ], $customMessages);

        $table_pekerjaan = tblPekerjaan::where('id', '=', $request->id)
            ->first();

        if (!empty($table_pekerjaan)) {
            try {
                $table_pekerjaan->update([
                    'id' => $request->id,
                    'nama_pekerjaan' => $request->nama_pekerjaan,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data Pekerjaan'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Pekerjaan berhasil diupdate',
                'data_pekerjaan' => $table_pekerjaan,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pekerjaan tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_pekerjaan = tblPekerjaan::where('id', '=', $request->id)
            ->first();

        if (!empty($table_pekerjaan)) {
            try {
                $table_pekerjaan = tblPekerjaan::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Pekerjaan berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data Pekerjaan'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Pekerjaan tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
