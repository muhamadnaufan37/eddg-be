<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblKelasPeserta;
use Illuminate\Http\Request;

class KelasPesertaController extends Controller
{
    public function data_all_kelas()
    {
        $table_kelas = tblKelasPeserta::select(['id', 'nama_kelas'])
            ->groupBy('id', 'nama_kelas') // Mengelompokkan hasil berdasarkan nama_kelas
            ->orderByRaw('nama_kelas') // Mengurutkan hasil berdasarkan nama_kelas
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_kelas' => $table_kelas,
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

        $model = tblKelasPeserta::select([
            'id',
            'nama_kelas',
        ]);

        if (!empty($keyword)) {
            $table_kelas = $model->where('nama_kelas', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_kelas = $model->paginate($perPage);
        }

        $table_kelas->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_kelas' => $table_kelas,
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
            'nama_kelas' => 'required|max:225|unique:kelas_peserta_didik',
        ], $customMessages);

        $table_kelas = new tblKelasPeserta();
        $table_kelas->nama_kelas = $request->nama_kelas;
        try {
            $table_kelas->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data Kelas Peserta'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_kelas->created_at, $table_kelas->updated_at);

        return response()->json([
            'message' => 'Data Kelas Peserta berhasil ditambahkan',
            'data_kelas' => $table_kelas,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_kelas = tblKelasPeserta::where('id', '=', $request->id)->first();

        unset($table_kelas->created_at, $table_kelas->updated_at);

        if (!empty($table_kelas)) {
            return response()->json([
                'message' => 'Sukses',
                'data_kelas' => $table_kelas,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Kelas Peserta tidak ditemukan',
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
            'nama_kelas' => 'sometimes|required|max:225|string|unique:kelas_peserta_didik,nama_kelas,'.$request->id.',id',
        ], $customMessages);

        $table_kelas = tblKelasPeserta::where('id', '=', $request->id)
            ->first();

        if (!empty($table_kelas)) {
            try {
                $table_kelas->update([
                    'id' => $request->id,
                    'nama_kelas' => $request->nama_kelas,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data Kelas Peserta'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Kelas Peserta berhasil diupdate',
                'data_kelas' => $table_kelas,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Kelas Peserta tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_kelas = tblKelasPeserta::where('id', '=', $request->id)
            ->first();

        if (!empty($table_kelas)) {
            try {
                $table_kelas = tblKelasPeserta::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Kelas Peserta berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data Kelas Peserta'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Kelas Peserta tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
