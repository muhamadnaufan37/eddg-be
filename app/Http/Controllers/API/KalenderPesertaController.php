<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblKlnderPndidikan;
use Illuminate\Http\Request;

class KalenderPesertaController extends Controller
{
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblKlnderPndidikan::select([
            'id',
            'tahun_pelajaran',
            'semester_pelajaran',
            'status_pelajaran',
            'created_at',
        ]);

        $model->where('status_pelajaran', '=', true);
        $model->orderByRaw('created_at DESC NULLS LAST');

        if (!empty($keyword)) {
            $table_kalender_pendidikan = $model->where('tahun_pelajaran', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_kalender_pendidikan = $model->paginate($perPage);
        }

        $table_kalender_pendidikan->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_kalender_pendidikan' => $table_kalender_pendidikan,
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
            'tahun_pelajaran' => 'required|max:225|unique:kalender_pendidikan',
            'semester_pelajaran' => 'required|max:225',
            'status_pelajaran' => 'required',
        ], $customMessages);

        // Lakukan pengecekan apakah sudah ada status_pelajaran yang true
        $existingTrueStatus = tblKlnderPndidikan::where('status_pelajaran', true)->first();

        $table_kalender_pendidikan = new tblKlnderPndidikan();
        $table_kalender_pendidikan->tahun_pelajaran = $request->tahun_pelajaran;
        $table_kalender_pendidikan->semester_pelajaran = $request->semester_pelajaran;
        $table_kalender_pendidikan->status_pelajaran = $request->status_pelajaran;

        if ($existingTrueStatus) {
            return response()->json([
                'message' => 'Sudah ada status pelajaran yang aktif',
                'success' => false,
            ], 400);
        }

        try {
            $table_kalender_pendidikan->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_kalender_pendidikan->created_at, $table_kalender_pendidikan->updated_at);

        return response()->json([
            'message' => 'Data berhasil ditambahkan',
            'data_kalender_pendidikan' => $table_kalender_pendidikan,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_kalender_pendidikan = tblKlnderPndidikan::where('id', '=', $request->id)->first();

        if (!empty($table_kalender_pendidikan)) {
            return response()->json([
                'message' => 'Sukses',
                'data_kalender_pendidikan' => $table_kalender_pendidikan,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
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

        // Lakukan pengecekan apakah tahun_pelajaran sudah ada sebelumnya
        $existingTahunPelajaran = tblKlnderPndidikan::where('tahun_pelajaran', $request->tahun_pelajaran)->first();

        if ($existingTahunPelajaran && $existingTahunPelajaran->id != $request->id) {
            return response()->json([
                'message' => 'Tahun pelajaran sudah ada',
                'success' => false,
            ], 400);
        }

        // Lakukan pengecekan apakah sudah ada status_pelajaran yang true
        $existingTrueStatus = tblKlnderPndidikan::where('status_pelajaran', true)->first();

        if ($existingTrueStatus && $existingTrueStatus->id != $request->id) {
            return response()->json([
                'message' => 'Sudah ada status pelajaran yang aktif',
                'success' => false,
            ], 400);
        }

        // Validasi input
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'tahun_pelajaran' => 'sometimes|required|max:225',
            'semester_pelajaran' => 'required|max:225',
            'status_pelajaran' => 'required',
        ], $customMessages);

        // Temukan data kalender pendidikan berdasarkan ID
        $table_kalender_pendidikan = tblKlnderPndidikan::find($request->id);

        // Jika data ditemukan, lakukan pembaruan
        if (!empty($table_kalender_pendidikan)) {
            try {
                $table_kalender_pendidikan->tahun_pelajaran = $request->tahun_pelajaran;
                $table_kalender_pendidikan->semester_pelajaran = $request->semester_pelajaran;
                $table_kalender_pendidikan->status_pelajaran = $request->status_pelajaran;
                $table_kalender_pendidikan->save();
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data berhasil diupdate',
                'data_kalender_pendidikan' => $table_kalender_pendidikan,
                'success' => true,
            ], 200);
        }

        // Jika data tidak ditemukan, kirimkan respons dengan pesan error
        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 404);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_kalender_pendidikan = tblKlnderPndidikan::where('id', '=', $request->id)
            ->first();

        if (!empty($table_kalender_pendidikan)) {
            try {
                $table_kalender_pendidikan = tblKlnderPndidikan::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
