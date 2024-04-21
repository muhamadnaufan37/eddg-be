<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use Illuminate\Http\Request;

class DaerahController extends Controller
{
    public function list_daerah()
    {
        $table_daerah = dataDaerah::select(['id', 'nama_daerah'])
        ->groupBy('id', 'nama_daerah') // Menambahkan id ke dalam grup by
        ->orderBy('nama_daerah')
        ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_daerah' => $table_daerah,
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

        $model = dataDaerah::select([
            'id',
            'nama_daerah',
        ]);

        if (!empty($keyword)) {
            $table_daerah = $model->where('nama_daerah', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_daerah = $model->paginate($perPage);
        }

        $table_daerah->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_daerah' => $table_daerah,
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
            'nama_daerah' => 'required|max:225|unique:tabel_daerah',
        ], $customMessages);

        $tabel_daerah = new dataDaerah();
        $tabel_daerah->nama_daerah = ucwords(strtolower($request->nama_daerah));
        try {
            $tabel_daerah->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Daerah'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($tabel_daerah->created_at, $tabel_daerah->updated_at);

        return response()->json([
            'message' => 'Data Daerah berhasil ditambahkan',
            'data_daerah' => $tabel_daerah,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $tabel_daerah = dataDaerah::where('id', '=', $request->id)->first();

        unset($tabel_daerah->created_at, $tabel_daerah->updated_at);

        if (!empty($tabel_daerah)) {
            return response()->json([
                'message' => 'Sukses',
                'data_daerah' => $tabel_daerah,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Daerah tidak ditemukan',
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
            'nama_daerah' => 'required|max:225',
        ], $customMessages);

        $tabel_daerah = dataDaerah::where('id', '=', $request->id)
            ->first();

        if (!empty($tabel_daerah)) {
            try {
                $tabel_daerah->update([
                    'id' => $request->id,
                    'nama_daerah' => ucwords(strtolower($request->nama_daerah)),
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Daerah'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Daerah berhasil diupdate',
                'data_daerah' => $tabel_daerah,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Daerah tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $tabel_daerah = dataDaerah::where('id', '=', $request->id)
            ->first();

        if (!empty($tabel_daerah)) {
            try {
                $tabel_daerah = dataDaerah::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Daerah berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data Daerah'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Daerah tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
