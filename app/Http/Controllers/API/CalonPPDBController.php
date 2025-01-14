<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblCppdb;
use App\Models\tblKelasPeserta;
use App\Models\tblKlnderPndidikan;
use App\Models\tblPengajar;
use App\Models\dataSensusPeserta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use App\Models\logs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalonPPDBController extends Controller
{
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $statusKenaikan = $request->get('status_naik_kelas', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblCppdb::select([
            'cppdb.id',
            'cppdb.kode_cari_ppdb',
            'kalender_pendidikan.tahun_pelajaran AS tahun_akademik',
            'kalender_pendidikan.semester_pelajaran AS semester_akademik',
            'kelas_peserta_didik.nama_kelas',
            'pengajar.nama_pengajar',
            'data_peserta.nama_lengkap AS nama_peserta',
            'users.nama_lengkap AS nama_petugas',
            'cppdb.status_naik_kelas',
            'cppdb.created_at',
        ])
            ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
            ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
            ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
            ->leftJoin('data_peserta', 'cppdb.id_peserta', '=', 'data_peserta.id')
            ->leftJoin('users', 'cppdb.id_petugas', '=', 'users.id')
            ->where('kalender_pendidikan.status_pelajaran', 1);


        $model->orderByRaw('cppdb.created_at IS NULL, cppdb.created_at DESC');

        if (!empty($keyword)) {
            $model->where(function ($query) use ($keyword) {
                $query->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('cppdb.kode_cari_ppdb', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('kalender_pendidikan.tahun_pelajaran', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('kelas_peserta_didik.nama_kelas', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('pengajar.nama_pengajar', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%');
            });
        }

        if (!is_null($statusKenaikan)) {
            $model->where('cppdb.status_naik_kelas', '=', $statusKenaikan);
        }

        $table_calon_ppdb = $model->paginate($perPage);
        $table_calon_ppdb->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_ppdb' => $table_calon_ppdb,
            'success' => true,
        ], 200);
    }

    public function listByKbm(Request $request)
    {
        $user = Auth::user();
        $roleID = $user->role_id;
        $userID = Auth::id();
        $statusKenaikan = $request->get('status_naik_kelas', null);

        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblCppdb::select([
            'cppdb.id',
            'cppdb.kode_cari_ppdb',
            'kalender_pendidikan.tahun_pelajaran AS tahun_akademik',
            'kalender_pendidikan.semester_pelajaran AS semester_akademik',
            'kelas_peserta_didik.nama_kelas',
            'pengajar.nama_pengajar',
            'data_peserta.nama_lengkap AS nama_peserta',
            'users.nama_lengkap AS nama_petugas',
            'cppdb.status_naik_kelas',
            'cppdb.created_at',
        ])
            ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
            ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
            ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
            ->leftJoin('data_peserta', 'cppdb.id_peserta', '=', 'data_peserta.id')
            ->leftJoin('users', 'cppdb.id_petugas', '=', 'users.id')
            ->where('kalender_pendidikan.status_pelajaran', 1);

        // Apply orderByRaw before executing the query
        $model->orderByRaw('cppdb.created_at IS NULL, cppdb.created_at DESC');

        if ($roleID != 1) {
            $model->where('cppdb.id_petugas', $userID);
        }

        if (!is_null($statusKenaikan)) {
            $model->where('cppdb.status_naik_kelas', '=', $statusKenaikan);
        }

        if (!empty($keyword)) {
            $model->where(function ($query) use ($keyword) {
                $query->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('cppdb.kode_cari_ppdb', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('kalender_pendidikan.tahun_pelajaran', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('kelas_peserta_didik.nama_kelas', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('pengajar.nama_pengajar', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%');
            });
        }

        $table_calon_ppdb = $model->paginate($perPage);
        $table_calon_ppdb->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_ppdb' => $table_calon_ppdb,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();
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
            'id_thn_akademik' => 'required',
            'id_kelas' => 'required',
            'id_pengajar' => 'required',
            'id_peserta' => 'required',
            'id_petugas' => 'required',
        ], $customMessages);

        $currentYear = date('Y');

        // Membangun kode_cari_ppdb
        $kode_cari_ppdb = $currentYear . $request->id_thn_akademik . $request->id_kelas . $request->id_pengajar . $request->id_peserta . $request->id_petugas . generateRandomCode();

        // Memeriksa apakah sudah ada data dengan id_thn_akademik dan id_peserta yang sama
        $existingEntry = tblCppdb::where('id_thn_akademik', $request->id_thn_akademik)
            ->where('id_peserta', $request->id_peserta)
            ->first();

        if ($existingEntry) {
            return response()->json([
                'message' => 'Data dengan Tahun Akademik dan Peserta yang sama sudah terdaftar',
                'success' => false,
            ], 409);
        }

        $table_calon_ppdb = new tblCppdb();
        $table_calon_ppdb->kode_cari_ppdb = $kode_cari_ppdb;
        $table_calon_ppdb->id_thn_akademik = $request->id_thn_akademik;
        $table_calon_ppdb->id_kelas = $request->id_kelas;
        $table_calon_ppdb->id_pengajar = $request->id_pengajar;
        $table_calon_ppdb->id_peserta = $request->id_peserta;
        $table_calon_ppdb->id_petugas = $request->id_petugas;
        $table_calon_ppdb->status_naik_kelas = false;

        // Memeriksa apakah tahun akademik ditemukan dan memiliki status pelajaran true
        $tabel_akademik_tahun = tblKlnderPndidikan::find($request->id_thn_akademik);
        if (!$tabel_akademik_tahun || !$tabel_akademik_tahun->status_pelajaran) {
            return response()->json([
                'message' => 'Tahun Akademik tidak ditemukan atau status pelajaran tidak aktif',
                'success' => false,
            ], 404);
        }

        $tabel_kelas_peserta = tblKelasPeserta::find($request->id_kelas);
        if (!$tabel_kelas_peserta) {
            return response()->json([
                'message' => 'Kelas tidak ditemukan',
                'success' => false,
            ], 404);
        }

        $tabel_pengajar_peserta = tblPengajar::find($request->id_pengajar);
        if (!$tabel_pengajar_peserta) {
            return response()->json([
                'message' => 'Pengajar tidak ditemukan',
                'success' => false,
            ], 404);
        }

        $tabel_peserta_didik = dataSensusPeserta::find($request->id_peserta);
        if (!$tabel_peserta_didik) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan',
                'success' => false,
            ], 404);
        }

        $tabel_petugas_peserta = User::find($request->id_petugas);
        if (!$tabel_petugas_peserta) {
            return response()->json([
                'message' => 'Petugas tidak ditemukan',
                'success' => false,
            ], 404);
        }

        try {
            $table_calon_ppdb->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Create Data PPDB - [' . $table_calon_ppdb->kode_cari_ppdb . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ];
            logs::create($logAccount);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data PPDB' . $exception->getMessage(),
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
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_calon_ppdb = tblCppdb::select([
            'cppdb.id',
            'cppdb.kode_cari_ppdb',
            'cppdb.id_thn_akademik',
            'cppdb.id_kelas',
            'cppdb.id_pengajar',
            'cppdb.id_peserta',
            'kalender_pendidikan.tahun_pelajaran AS tahun_akademik',
            'kalender_pendidikan.semester_pelajaran AS semester_akademik',
            'kelas_peserta_didik.nama_kelas',
            'pengajar.nama_pengajar',
            'data_peserta.kode_cari_data',
            'data_peserta.nama_lengkap AS nama_peserta',
            'data_peserta.nama_ayah',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
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
            'cppdb.nilai11_1',
            'cppdb.nilai12',
            'cppdb.nilai13',
            'cppdb.nilai14',
            'cppdb.nilai15',
            'cppdb.nilai16',
            'cppdb.nilai_presensi_1',
            'cppdb.nilai_presensi_2',
            'cppdb.nilai_presensi_3',
            'cppdb.catatan_ortu',
            'cppdb.tgl_penetapan',
            'cppdb.tmpt_penetapan',
            'cppdb.status_naik_kelas',
        ])
            ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
            ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
            ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
            ->leftJoin('data_peserta', 'cppdb.id_peserta', '=', 'data_peserta.id')
            ->leftJoin('users', 'cppdb.id_petugas', '=', 'users.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id') // Menghubungkan tabel desa
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id') // Menghubungkan tabel desa
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id') // Menghubungkan tabel kelompok
            ->where('cppdb.id', $request->id)->first();

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
        $userId = Auth::id();
        $agent = new Agent();
        $tabel_kelas_peserta = tblKelasPeserta::find($request->id_kelas);
        $tabel_pengajar_peserta = tblPengajar::find($request->id_pengajar);
        $tabel_peserta_didik = dataSensusPeserta::find($request->id_peserta);
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

        // Ambil data peserta saat ini berdasarkan ID yang dikirimkan
        $currentEntry = tblCppdb::find($request->id);

        if ($currentEntry) {
            // Periksa apakah id_peserta dalam permintaan berbeda dari id_peserta saat ini
            if ($currentEntry->id_peserta != $request->id_peserta) {
                // Jika id_peserta diubah, cek apakah kombinasi id_thn_akademik dan id_peserta baru sudah ada
                $existingEntry = tblCppdb::where('id_thn_akademik', $request->id_thn_akademik)
                    ->where('id_peserta', $request->id_peserta)
                    ->first();

                if ($existingEntry) {
                    return response()->json([
                        'message' => 'Data dengan Tahun Akademik dan Peserta yang sama sudah terdaftar',
                        'success' => false,
                    ], 409);
                }
            }
        }

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'id_kelas' => 'required|numeric|digits_between:1,5',
            'id_pengajar' => 'required|numeric|digits_between:1,5',
            'id_peserta' => 'sometimes|required|numeric|digits_between:1,5',
        ], $customMessages);

        $table_calon_ppdb = tblCppdb::where('id', '=', $request->id)
            ->first();

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
                $originalData = $table_calon_ppdb->getOriginal();

                $table_calon_ppdb->fill([
                    'id_kelas' => $request->id_kelas,
                    'id_pengajar' => $request->id_pengajar,
                    'id_peserta' => $request->id_peserta,
                ]);

                $updatedFields = [];
                foreach ($table_calon_ppdb->getDirty() as $field => $newValue) {
                    $oldValue = $originalData[$field] ?? null; // Ambil nilai lama
                    $updatedFields[] = "$field: [$oldValue] -> [$newValue]";
                }

                // Simpan perubahan ke database
                $table_calon_ppdb->save();

                // Log perubahan
                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Update Data Penilaian PPDB',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    'engine_agent' => $request->header('user-agent'),
                    'updated_fields' => json_encode($updatedFields), // Simpan sebagai JSON
                ];
                logs::create($logAccount);

                return response()->json([
                    'message' => 'Data PPDB berhasil diupdate',
                    'data_ppdb' => $table_calon_ppdb,
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data PPDB: ' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data PPDB tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function updatePenilaian(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();

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
            'nilai1' => 'required|numeric|digits_between:1,3',
            'nilai2' => 'required|numeric|digits_between:1,3',
            'nilai3' => 'required|numeric|digits_between:1,3',
            'nilai4' => 'required|numeric|digits_between:1,3',
            'nilai5' => 'required|numeric|digits_between:1,3',
            'nilai6' => 'required|numeric|digits_between:1,3',
            'nilai7' => 'required|numeric|digits_between:1,3',
            'nilai8' => 'required|numeric|digits_between:1,3',
            'nilai9' => 'required|numeric|digits_between:1,3',
            'nilai10' => 'required|numeric|digits_between:1,3',
            'nilai11' => 'required|numeric|digits_between:1,3',
            'nilai11_1' => 'required|numeric|digits_between:1,3',
            'nilai12' => 'required',
            'nilai13' => 'required',
            'nilai14' => 'required',
            'nilai15' => 'required',
            'nilai16' => 'required',
            'nilai_presensi_1' => 'required',
            'nilai_presensi_2' => 'required',
            'nilai_presensi_3' => 'required',
            'catatan_ortu' => 'required',
            'tmpt_penetapan' => 'required',
            'status_naik_kelas' => 'required',
        ], $customMessages);

        $table_calon_ppdb = tblCppdb::where('id', '=', $request->id)
            ->first();

        if (!empty($table_calon_ppdb)) {
            try {

                $originalData = $table_calon_ppdb->getOriginal();

                $table_calon_ppdb->fill([
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
                    'nilai11_1' => $request->nilai11_1,
                    'nilai12' => $request->nilai12,
                    'nilai13' => $request->nilai13,
                    'nilai14' => $request->nilai14,
                    'nilai15' => $request->nilai15,
                    'nilai16' => $request->nilai16,
                    'nilai_presensi_1' => $request->nilai_presensi_1,
                    'nilai_presensi_2' => $request->nilai_presensi_2,
                    'nilai_presensi_3' => $request->nilai_presensi_3,
                    'catatan_ortu' => $request->catatan_ortu,
                    'tgl_penetapan' => Carbon::now()->format('Y-m-d H:i:s'),
                    'tmpt_penetapan' => $request->tmpt_penetapan,
                    'status_naik_kelas' => $request->status_naik_kelas,
                ]);

                $updatedFields = [];
                foreach ($table_calon_ppdb->getDirty() as $field => $newValue) {
                    $oldValue = $originalData[$field] ?? null; // Ambil nilai lama
                    $updatedFields[] = "$field: [$oldValue] -> [$newValue]";
                }

                // Simpan perubahan ke database
                $table_calon_ppdb->save();

                // Log perubahan
                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Update Data PPDB',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    'engine_agent' => $request->header('user-agent'),
                    'updated_fields' => json_encode($updatedFields), // Simpan sebagai JSON
                ];
                logs::create($logAccount);

                return response()->json([
                    'message' => 'Data PPDB berhasil diupdate',
                    'data_ppdb' => $table_calon_ppdb,
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data PPDB' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data PPDB tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        // Cari data terlebih dahulu
        $table_calon_ppdb = tblCppdb::where('id', '=', $request->id)->first();

        if ($table_calon_ppdb) {
            try {
                // Simpan data sebelum dihapus untuk log
                $deletedData = $table_calon_ppdb->toArray();

                // Hapus data
                $table_calon_ppdb->delete();

                // Buat log
                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Delete Data PPDB - [' . $deletedData['id'] . ']',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    'engine_agent' => $request->header('user-agent'),
                ];
                logs::create($logAccount);

                return response()->json([
                    'message' => 'Data PPDB berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data PPDB: ' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data PPDB tidak ditemukan',
            'success' => false,
        ], 404);
    }

    public function getPesertaBelumInputPenilaian(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $dataDaerah = $request->get('data-daerah', $user->role_daerah);
        $dataDesa = $request->get('data-desa', $user->role_desa);
        $dataKelompok = $request->get('data-kelompok', $user->role_kelompok);

        // Batasi maksimum per halaman
        $perPage = min($perPage, 100);

        // Query peserta yang belum ada di cppdb
        $pesertaQuery = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.nama_lengkap',
            'data_peserta.tmpt_daerah',
            'data_peserta.tmpt_desa',
            'data_peserta.tmpt_kelompok',
        ])
            ->leftJoin('cppdb', function ($join) {
                $join->on('data_peserta.id', '=', 'cppdb.id_peserta')
                    ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
                    ->where('kalender_pendidikan.status_pelajaran', 1);
            })
            ->whereNull('cppdb.id_peserta') // Peserta yang belum diinputkan penilaian
            ->where('data_peserta.jenis_data', 'KBM') // Hanya data peserta dengan jenis_data KBM
            ->where(function ($query) {
                $query->whereNull('kalender_pendidikan.id')
                    ->orWhere('kalender_pendidikan.status_pelajaran', 1);
            });

        // Filter berdasarkan keyword jika ada
        if (!empty($keyword)) {
            $pesertaQuery->where(function ($query) use ($keyword) {
                $query->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.jenis_kelamin', 'LIKE', '%' . $keyword . '%');
            });
        }

        // Filter lokasi jika ada
        if (!is_null($dataDaerah)) {
            $pesertaQuery->where('data_peserta.tmpt_daerah', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $pesertaQuery->where('data_peserta.tmpt_desa', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $pesertaQuery->where('data_peserta.tmpt_kelompok', $dataKelompok);
        }

        // Pagination dan pengambilan data
        $paginatedData = $pesertaQuery->paginate($perPage);

        // Transformasi data untuk response
        $transformedData = $paginatedData->map(function ($peserta) {
            return [
                'id_peserta' => $peserta->id,
                'nama_lengkap' => $peserta->nama_lengkap,
                'tmpt_daerah' => $peserta->tmpt_daerah,
                'tmpt_desa' => $peserta->tmpt_desa,
                'tmpt_kelompok' => $peserta->tmpt_kelompok,
                'status_naik_kelas' => 'Belum Diinputkan', // Semua data yang belum diinputkan
            ];
        });

        // Statistik
        $statistics = [
            'peserta_belum_diinputkan' => $paginatedData->total(),
        ];

        // Response JSON
        return response()->json([
            'message' => 'Data peserta yang belum diinputkan penilaian berhasil diambil.',
            'data_presensi_peserta' => $transformedData,
            'statistics' => $statistics,
            'success' => true,
        ], 200);
    }
}
