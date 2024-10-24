<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblKlnderPndidikan;
use Illuminate\Http\Request;

class KalenderPesertaController extends Controller
{
    public function data_all_kalender_aktif()
    {
        $table_kalender_pendidikan = tblKlnderPndidikan::select(['id', 'tahun_pelajaran', 'semester_pelajaran'])
            ->where('status_pelajaran', true) // Memfilter hasil berdasarkan status_pelajaran
            ->groupBy('id', 'tahun_pelajaran', 'semester_pelajaran') // Mengelompokkan hasil berdasarkan tahun_pelajaran dan semester_pelajaran
            ->orderByRaw('tahun_pelajaran ASC, semester_pelajaran ASC') // Mengurutkan hasil berdasarkan tahun_pelajaran dan semester_pelajaran
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_kalender_pendidikan' => $table_kalender_pendidikan,
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

        $model = tblKlnderPndidikan::select([
            'id',
            'tahun_pelajaran',
            'semester_pelajaran',
            'status_pelajaran',
            'created_at',
        ]);

        // $model->where('status_pelajaran', '=', true);
        // Apply orderByRaw before executing the query
        $model->orderByRaw('created_at IS NULL, created_at DESC');

        if (!empty($keyword)) {
            $table_kalender_pendidikan = $model->where('tahun_pelajaran', 'LIKE', '%'.$keyword.'%')
                ->orWhere('id', 'LIKE', '%'.$keyword.'%')
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

        // Pastikan hanya ada satu catatan dengan status_pelajaran bernilai true
        $countTrueStatus = tblKlnderPndidikan::where('status_pelajaran', 1)->count();

        // Jika sudah ada satu catatan dengan status_pelajaran bernilai true dan mencoba menyimpan lagi, kembalikan respons yang sesuai
        if ($countTrueStatus >= 1 && $request->status_pelajaran === 1) {
            return response()->json(['message' => 'Hanya boleh ada satu catatan dengan status pelajaran Aktif'], 400);
        }

        // Lanjutkan dengan validasi dan penyimpanan data jika tidak ada lebih dari satu catatan dengan status_pelajaran bernilai true
        $request->validate([
            'tahun_pelajaran' => 'required|max:225',
            'semester_pelajaran' => 'required|max:225',
            'status_pelajaran' => 'required',
        ], $customMessages);

        // Lakukan pengecekan apakah sudah ada tahun_pelajaran yang sama untuk semester tertentu
        $existingRecord = tblKlnderPndidikan::where('tahun_pelajaran', $request->tahun_pelajaran)
            ->where('semester_pelajaran', $request->semester_pelajaran)
            ->first();

        // Jika ada catatan yang sudah ada, hentikan proses dan berikan respons yang sesuai
        if ($existingRecord) {
            return response()->json(['message' => 'Data sudah ada untuk tahun pelajaran dan semester yang sama'], 400);
        }

        $table_kalender_pendidikan = new tblKlnderPndidikan();
        $table_kalender_pendidikan->tahun_pelajaran = $request->tahun_pelajaran;
        $table_kalender_pendidikan->semester_pelajaran = $request->semester_pelajaran;
        $table_kalender_pendidikan->status_pelajaran = $request->status_pelajaran;

        if ($existingRecord) {
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

        // Validasi input
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'tahun_pelajaran' => 'sometimes|required|max:225',
            'semester_pelajaran' => 'sometimes|required|max:225',
            'status_pelajaran' => 'required',
        ], $customMessages);

        // Lakukan pengecekan apakah ada perubahan pada tahun_pelajaran atau semester_pelajaran
        if ($request->has('tahun_pelajaran') && $request->has('semester_pelajaran') && $request->has('status_pelajaran')) {
            $tahun_pelajaran = $request->input('tahun_pelajaran');
            $semester_pelajaran = $request->input('semester_pelajaran');
            $status_pelajaran = $request->input('status_pelajaran');

            // Ambil data kalender pendidikan berdasarkan id
            $kalender_pendidikan = tblKlnderPndidikan::findOrFail($request->id);

            // Pastikan ada perubahan pada tahun_pelajaran atau semester_pelajaran
            if ($kalender_pendidikan->tahun_pelajaran != $tahun_pelajaran || $kalender_pendidikan->semester_pelajaran != $semester_pelajaran || $kalender_pendidikan->status_pelajaran != $status_pelajaran) {
                // Pastikan hanya ada satu catatan dengan status_pelajaran bernilai true
                $countTrueStatus = tblKlnderPndidikan::where('status_pelajaran', 1)->count();

                // Jika sudah ada satu catatan dengan status_pelajaran bernilai true dan mencoba menyimpan lagi, kembalikan respons yang sesuai
                if ($countTrueStatus >= 1 && $request->status_pelajaran === 1) {
                    return response()->json(['message' => 'Hanya boleh ada satu catatan dengan status pelajaran Aktif'], 400);
                }

                // Lakukan pengecekan apakah sudah ada tahun_pelajaran yang sama untuk semester tertentu
                $existingRecord = tblKlnderPndidikan::where('tahun_pelajaran', $tahun_pelajaran)
                    ->where('semester_pelajaran', $semester_pelajaran)
                    ->first();

                // Jika ada catatan yang sudah ada, hentikan proses dan berikan respons yang sesuai
                if ($existingRecord && $existingRecord->id != $request->id) {
                    return response()->json(['message' => 'Data sudah ada untuk tahun pelajaran dan semester yang sama'], 400);
                }
            }
        }

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
