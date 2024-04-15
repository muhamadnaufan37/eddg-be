<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\rerataNilai;
use Illuminate\Http\Request;

class RerataNilaiController extends Controller
{
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = rerataNilai::select([
            'id',
            'r_nilai1',
            'r_nilai2',
            'r_nilai3',
            'r_nilai4',
            'r_nilai5',
            'r_nilai6',
            'r_nilai7',
            'r_nilai8',
            'r_nilai9',
            'r_nilai10',
            'r_nilai11',
            'created_at',
        ]);

        if (!empty($keyword)) {
            $table_rerata_nilai = $model->where('id', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_rerata_nilai = $model->paginate($perPage);
        }

        $table_rerata_nilai->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_rerata_nilai' => $table_rerata_nilai,
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

        // Cek apakah sudah ada data di tabel rerata_nilai
        $countRerataNilai = rerataNilai::count();

        if ($countRerataNilai > 0) {
            return response()->json([
                'message' => 'Tabel Rerata Nilai sudah memiliki data',
                'success' => false,
            ], 400);
        }

        // Validasi input
        $request->validate([
            'r_nilai1' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai2' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai3' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai4' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai5' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai6' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai7' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai8' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai9' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai10' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai11' => 'required|integer|digits_between:1,2|max:100',
        ], $customMessages);

        // Hitung total nilai
        // $totalNilai = $request->nilai1 + $request->nilai2 + $request->nilai3 + $request->nilai4 +
        //     $request->nilai5 + $request->nilai6 + $request->nilai7 + $request->nilai8 +
        //     $request->nilai9 + $request->nilai10 + $request->nilai11;

        // Pastikan total nilai tidak melebihi 100
        // if ($totalNilai > 100) {
        //     return response()->json([
        //         'message' => 'Total nilai melebihi 100',
        //         'success' => false,
        //     ], 400);
        // }

        // Buat entri baru ke tabel rerata_nilai
        $table_rerata_nilai = new rerataNilai();
        $table_rerata_nilai->r_nilai1 = $request->r_nilai1;
        $table_rerata_nilai->r_nilai2 = $request->r_nilai2;
        $table_rerata_nilai->r_nilai3 = $request->r_nilai3;
        $table_rerata_nilai->r_nilai4 = $request->r_nilai4;
        $table_rerata_nilai->r_nilai5 = $request->r_nilai5;
        $table_rerata_nilai->r_nilai6 = $request->r_nilai6;
        $table_rerata_nilai->r_nilai7 = $request->r_nilai7;
        $table_rerata_nilai->r_nilai8 = $request->r_nilai8;
        $table_rerata_nilai->r_nilai9 = $request->r_nilai9;
        $table_rerata_nilai->r_nilai10 = $request->r_nilai10;
        $table_rerata_nilai->r_nilai11 = $request->r_nilai11;

        try {
            $table_rerata_nilai->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data Rerata Nilai'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_rerata_nilai->created_at, $table_rerata_nilai->updated_at);

        return response()->json([
            'message' => 'Data Rerata Nilai berhasil ditambahkan',
            'data_rerata_nilai' => $table_rerata_nilai,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_rerata_nilai = rerataNilai::where('id', '=', $request->id)->first();

        unset($table_rerata_nilai->created_at, $table_rerata_nilai->updated_at);

        if (!empty($table_rerata_nilai)) {
            return response()->json([
                'message' => 'Sukses',
                'data_rerata_nilai' => $table_rerata_nilai,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Rerata Nilai tidak ditemukan',
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
            'r_nilai1' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai2' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai3' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai4' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai5' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai6' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai7' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai8' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai9' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai10' => 'required|integer|digits_between:1,2|max:100',
            'r_nilai11' => 'required|integer|digits_between:1,2|max:100',
        ], $customMessages);

        $table_rerata_nilai = rerataNilai::where('id', '=', $request->id)
            ->first();

        if (!empty($table_rerata_nilai)) {
            try {
                $table_rerata_nilai->update([
                    'id' => $request->id,
                    'nilai1' => $request->nilai1,
                    'nilai2' => $request->nilai2,
                    'nilai3' => $request->nilai3,
                    'nilai4' => $request->nilai4,
                    'nilai5' => $request->nilai5,
                    'nilai6' => $request->nilai6,
                    'nilai7' => $request->nilai7,
                    'nilai8' => $request->nilai8,
                    'nilai9' => $request->nilai9,
                    'nilai10' => $request->nilai10,
                    'nilai11' => $request->nilai11,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data Rerata Nilai'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Rerata Nilai berhasil diupdate',
                'data_rerata_nilai' => $table_rerata_nilai,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Rerata Nilai tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_rerata_nilai = rerataNilai::where('id', '=', $request->id)
            ->first();

        if (!empty($table_rerata_nilai)) {
            try {
                $table_rerata_nilai = rerataNilai::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Rerata Nilai berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data Rerata Nilai'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Rerata Nilai tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
