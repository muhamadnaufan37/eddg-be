<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDesa;
use Exception;
use Illuminate\Http\Request;

class DesaController extends Controller
{
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = dataDesa::select([
            'id',
            'nama_desa',
        ]);

        if (!empty($keyword)) {
            $table_desa = $model->where('nama_desa', 'ILIKE', '%' . $keyword . '%')
                ->orWhere('id', 'ILIKE', '%' . $keyword . '%')
                ->paginate($perPage);
        } else {
            $table_desa = $model->paginate($perPage);
        }

        $table_desa->appends(['per-page' => $perPage]);

        return response()->json([
            'message'   => 'Sukses',
            'data_desa' => $table_desa,
            'success'   => true
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
            'nama_desa' => 'required|max:225|unique:tabel_desa',
        ], $customMessages);

        $tabel_desa = new dataDesa;
        $tabel_desa->nama_desa = $request->nama_desa;
        try {
            $tabel_desa->save();
        } catch (Exception $exception) {
            return response()->json([
                'message'   => 'Gagal menambah data Desa' . $exception->getMessage(),
                'success'   => false
            ], 500);
        }

        unset($tabel_desa->created_at, $tabel_desa->updated_at);

        return response()->json([
            'message'   => 'Data Desa berhasil ditambahkan',
            'data_desa' => $tabel_desa,
            'success'   => true
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5'
        ]);

        $tabel_desa = dataDesa::where('id', '=', $request->id)->first();

        unset($tabel_desa->created_at, $tabel_desa->updated_at);

        if (!empty($tabel_desa)) {
            return response()->json([
                'message'   => 'Sukses',
                'data_desa' => $tabel_desa,
                'success'   => true
            ], 200);
        }

        return response()->json([
            'message'   => 'Data Desa tidak ditemukan',
            'success'   => false
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
            'nama_desa' => 'required|max:225',
        ], $customMessages);

        $tabel_desa = dataDesa::where('id', '=', $request->id)
            ->first();

        if (!empty($tabel_desa)) {
            try {
                $tabel_desa->update([
                    'id' => $request->id,
                    'nama_desa' => $request->nama_desa,
                ]);
            } catch (Exception $exception) {
                return response()->json([
                    'message'   => 'Gagal mengupdate data Desa' . $exception->getMessage(),
                    'success'   => false
                ], 500);
            }

            return response()->json([
                'message'   => 'Data Desa berhasil diupdate',
                'data_desa' => $tabel_desa,
                'success'   => true
            ], 200);
        }

        return response()->json([
            'message'   => 'Data Desa tidak ditemukan',
            'success'   => false
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5'
        ]);

        $tabel_desa = dataDesa::where('id', '=', $request->id)
            ->first();

        if (!empty($tabel_desa)) {
            try {
                $tabel_desa = dataDesa::where('id', '=', $request->id)
                    ->delete();
                return response()->json([
                    'message'   => 'Data Desa berhasil dihapus',
                    'success'   => true
                ], 200);
            } catch (Exception $exception) {
                return response()->json([
                    'message'   => 'Gagal menghapus data Desa' . $exception->getMessage(),
                    'success'   => false
                ], 500);
            }
        }

        return response()->json([
            'message'   => 'Data Desa tidak ditemukan',
            'success'   => false
        ], 200);
    }
}
