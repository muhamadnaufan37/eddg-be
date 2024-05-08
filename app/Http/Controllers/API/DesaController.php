<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use Illuminate\Http\Request;

class DesaController extends Controller
{
    public function list_desa()
    {
        $table_desa = dataDesa::select(['id', 'nama_desa'])
        ->groupBy('id', 'nama_desa') // Menambahkan id ke dalam grup by
        ->orderBy('nama_desa')
        ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_desa' => $table_desa,
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

        $model = dataDesa::select([
            'tabel_desa.id',
            'tabel_desa.nama_desa',
            'tabel_daerah.nama_daerah AS parent_daerah',
            'tabel_desa.daerah_id',
        ])
        ->leftJoin('tabel_daerah', 'tabel_desa.daerah_id', '=', 'tabel_daerah.id');

        if (!empty($keyword)) {
            $table_desa = $model->where('tabel_desa.nama_desa', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('tabel_desa.id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_desa = $model->paginate($perPage);
        }

        $table_desa->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_desa' => $table_desa,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $tabel_daerah = dataDaerah::find($request->daerah_id);

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
            'daerah_id' => 'required|numeric',
        ], $customMessages);

        $tabel_desa = new dataDesa();
        $tabel_desa->nama_desa = $request->nama_desa;
        $tabel_desa->daerah_id = $request->daerah_id;
        try {
            if (!$tabel_daerah) {
                return response()->json([
                    'message' => 'Daerah dengan ID yang diberikan tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            $tabel_desa->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Desa'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($tabel_desa->created_at, $tabel_desa->updated_at);

        return response()->json([
            'message' => 'Data Desa berhasil ditambahkan',
            'data_desa' => $tabel_desa,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $tabel_desa = dataDesa::where('id', '=', $request->id)->first();

        unset($tabel_desa->created_at, $tabel_desa->updated_at);

        if (!empty($tabel_desa)) {
            return response()->json([
                'message' => 'Sukses',
                'data_desa' => $tabel_desa,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Desa tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update(Request $request)
    {
        $tabel_daerah = dataDaerah::find($request->daerah_id);

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
            'nama_desa' => 'sometimes|required|string|unique:tabel_desa,nama_desa,'.$request->id.',id',
            'daerah_id' => 'required|numeric',
        ], $customMessages);

        $tabel_desa = dataDesa::where('id', '=', $request->id)
            ->first();

        if (!empty($tabel_desa)) {
            if (!$tabel_daerah) {
                return response()->json([
                    'message' => 'Daerah dengan ID yang diberikan tidak ditemukan',
                    'success' => false,
                ], 404);
            }
            try {
                $tabel_desa->update([
                    'id' => $request->id,
                    'nama_desa' => $request->nama_desa,
                    'daerah_id' => $request->daerah_id,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Desa'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Desa berhasil diupdate',
                'data_desa' => $tabel_desa,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Desa tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $tabel_desa = dataDesa::where('id', '=', $request->id)
            ->first();

        if (!empty($tabel_desa)) {
            try {
                $tabel_desa = dataDesa::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Desa berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data Desa'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Desa tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
