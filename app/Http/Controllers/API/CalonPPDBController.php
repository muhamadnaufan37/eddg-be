<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblCppdb;
use App\Models\tblKelasPeserta;
use App\Models\tblKlnderPndidikan;
use App\Models\tblPengajar;
use App\Models\tblPesertaDidik;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class CalonPPDBController extends Controller
{
    public function list(Request $request)
    {
        $userID = Auth::id();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblCppdb::select([
            'cppdb.uuid',
            'cppdb.kode_cari_ppdb',
            'kalender_pendidikan.tahun_pelajaran AS tahun_akademik',
            'kalender_pendidikan.semester_pelajaran AS semester_akademik',
            'kelas_peserta_didik.nama_kelas',
            'pengajar.nama_pengajar',
            'peserta_didik.nama_lengkap AS nama_peserta',
            'users.nama_lengkap AS nama_petugas',
            'cppdb.created_at',
        ])
        ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
        ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
        ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
        ->leftJoin('peserta_didik', 'cppdb.id_peserta', '=', 'peserta_didik.id')
        ->leftJoin('users', 'cppdb.id_petugas', '=', 'users.id')
        ->where('cppdb.id_petugas', $userID); // Filter berdasarkan ID pengguna yang sedang login;

        if (!empty($keyword)) {
            $table_calon_ppdb = $model->where('kode_cari_ppdb', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_calon_ppdb = $model->paginate($perPage);
        }

        $table_calon_ppdb->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_ppdb' => $table_calon_ppdb,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $tabel_akademin_tahun = tblKlnderPndidikan::find($request->id_thn_akademik);
        $tabel_kelas_peserta = tblKelasPeserta::find($request->id_kelas);
        $tabel_pengajar_peserta = tblPengajar::find($request->id_pengajar);
        $tabel_peserta_didik = tblPesertaDidik::find($request->id_peserta);
        $tabel_petugas_peserta = User::find($request->id_petugas);

        // Menghasilkan UUID
        $uuid = Uuid::uuid4()->toString();

        function generateRandomCode($length = 5)
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomCode = '';
            for ($i = 0; $i < $length; ++$i) {
                $randomCode .= $characters[rand(0, strlen($characters) - 1)];
            }

            return $randomCode;
        }

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
            'kode_cari_ppdb' => 'unique:cppdb',
            'id_thn_akademik' => 'required|unique:cppdb',
            'id_kelas' => 'required',
            'id_pengajar' => 'required',
            'id_peserta' => 'required',
            'id_petugas' => 'required',
        ], $customMessages);

        $currentYear = date('Y');

        // Membangun kode_cari_ppdb
        $kode_cari_ppdb = $currentYear.$request->id_thn_akademik.$request->id_kelas.$request->id_pengajar.$request->id_peserta.$request->id_petugas.generateRandomCode();

        $table_calon_ppdb = new tblCppdb();
        $table_calon_ppdb->uuid = $uuid; // Atur nilai uuid
        $table_calon_ppdb->kode_cari_ppdb = $kode_cari_ppdb;
        $table_calon_ppdb->id_thn_akademik = $request->id_thn_akademik;
        $table_calon_ppdb->id_kelas = $request->id_kelas;
        $table_calon_ppdb->id_pengajar = $request->id_pengajar;
        $table_calon_ppdb->id_peserta = $request->id_peserta;
        $table_calon_ppdb->id_petugas = $request->id_petugas;

        // Memeriksa apakah tahun akademik ditemukan dan memiliki status pelajaran true
        if (!$tabel_akademin_tahun || !$tabel_akademin_tahun->status_pelajaran) {
            return response()->json([
                'message' => 'Tahun Akademik tidak ditemukan atau status pelajaran tidak aktif',
                'success' => false,
            ], 404);
        }

        if (!$tabel_kelas_peserta) {
            return response()->json([
                'message' => 'Kelas tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (!$tabel_pengajar_peserta) {
            return response()->json([
                'message' => 'Pengajar tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (!$tabel_peserta_didik) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (!$tabel_petugas_peserta) {
            return response()->json([
                'message' => 'Petugas tidak ditemukan',
                'success' => false,
            ], 404);
        }

        try {
            $table_calon_ppdb->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data PPDB'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_calon_ppdb->created_at, $table_calon_ppdb->updated_at);

        return response()->json([
            'message' => 'Data PPDB berhasil ditambahkan',
            'data_ppdb' => $table_calon_ppdb,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid',
        ]);

        // $table_calon_ppdb = tblCppdb::where('uuid', '=', $request->uuid)->first();

        $table_calon_ppdb = tblCppdb::select([
            'cppdb.uuid',
            'cppdb.kode_cari_ppdb',
            'kalender_pendidikan.tahun_pelajaran AS tahun_akademik',
            'kalender_pendidikan.semester_pelajaran AS semester_akademik',
            'kelas_peserta_didik.nama_kelas',
            'pengajar.nama_pengajar',
            'peserta_didik.nama_lengkap AS nama_peserta',
            'users.nama_lengkap AS nama_petugas',
            'cppdb.nilai1',
            'cppdb.nilai2',
            'cppdb.nilai3',
            'cppdb.nilai4',
            'cppdb.nilai5',
            'cppdb.nilai6',
            'cppdb.nilai7',
            'cppdb.nilai8',
            'cppdb.nilai9',
            'cppdb.nilai10',
            'cppdb.nilai11',
            'cppdb.nilai_presensi_1',
            'cppdb.nilai_presensi_2',
            'cppdb.nilai_presensi_3',
            'cppdb.catatan_ortu',
            'cppdb.status_naik_kelas',
        ])
        ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
        ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
        ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
        ->leftJoin('peserta_didik', 'cppdb.id_peserta', '=', 'peserta_didik.id')
        ->leftJoin('users', 'cppdb.id_petugas', '=', 'users.id')->first();

        unset($table_calon_ppdb->created_at, $table_calon_ppdb->updated_at);

        if (!empty($table_calon_ppdb)) {
            return response()->json([
                'message' => 'Sukses',
                'data_ppdb' => $table_calon_ppdb,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data PPDB tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update(Request $request)
    {
        $tabel_akademin_tahun = tblKlnderPndidikan::find($request->id_thn_akademik);
        $tabel_kelas_peserta = tblKelasPeserta::find($request->id_kelas);
        $tabel_pengajar_peserta = tblPengajar::find($request->id_pengajar);
        $tabel_peserta_didik = tblPesertaDidik::find($request->id_peserta);
        $tabel_petugas_peserta = User::find($request->id_petugas);

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
            'uuid' => 'required|uuid',
            'id_thn_akademik' => 'sometimes|required|numeric|digits_between:1,5|unique:cppdb,id_thn_akademik,'.$request->uuid.',uuid',
            'id_kelas' => 'sometimes|required|numeric|digits_between:1,5|unique:cppdb,id_kelas,'.$request->uuid.',uuid',
            'id_pengajar' => 'sometimes|required|numeric|digits_between:1,5|unique:cppdb,id_pengajar,'.$request->uuid.',uuid',
            'id_peserta' => 'sometimes|required|numeric|digits_between:1,5|unique:cppdb,id_peserta,'.$request->uuid.',uuid',
            'id_petugas' => 'sometimes|required|numeric|digits_between:1,5|unique:cppdb,id_petugas,'.$request->uuid.',uuid',
        ], $customMessages);

        $table_calon_ppdb = tblCppdb::where('uuid', '=', $request->uuid)
            ->first();

        // Memeriksa apakah tahun akademik ditemukan dan memiliki status pelajaran true
        if (!$tabel_akademin_tahun || !$tabel_akademin_tahun->status_pelajaran) {
            return response()->json([
                'message' => 'Tahun Akademik tidak ditemukan atau status pelajaran tidak aktif',
                'success' => false,
            ], 404);
        }

        if (!$tabel_kelas_peserta) {
            return response()->json([
                'message' => 'Kelas tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (!$tabel_pengajar_peserta) {
            return response()->json([
                'message' => 'Pengajar tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (!$tabel_peserta_didik) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (!$tabel_petugas_peserta) {
            return response()->json([
                'message' => 'Petugas tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (!empty($table_calon_ppdb)) {
            try {
                $table_calon_ppdb->update([
                    'uuid' => $request->uuid,
                    'id_thn_akademik' => $request->id_thn_akademik,
                    'id_kelas' => $request->id_kelas,
                    'id_pengajar' => $request->id_pengajar,
                    'id_peserta' => $request->id_peserta,
                    'id_petugas' => $request->id_petugas,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data PPDB'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data PPDB berhasil diupdate',
                'data_ppdb' => $table_calon_ppdb,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data PPDB tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid',
        ]);

        $table_calon_ppdb = tblCppdb::where('uuid', '=', $request->uuid)
            ->first();

        if (!empty($table_calon_ppdb)) {
            try {
                $table_calon_ppdb = tblCppdb::where('uuid', '=', $request->uuid)
                    ->delete();

                return response()->json([
                    'message' => 'Data PPDB berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data PPDB'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data PPDB tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
