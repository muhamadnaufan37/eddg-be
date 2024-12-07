<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblCppdb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanRaporController extends Controller
{
    public function getAverageNilai1(Request $request)
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
            'id_thn_akademik' => 'required|numeric',
            'id_daerah' => 'required|numeric',
            'kelas' => 'required|numeric',
        ], $customMessages);

        $dataThnAkademik = $request->get('id_thn_akademik');
        $dataDaerah = $request->get('id_daerah', null);
        $dataDesa = $request->get('id_desa', null);
        $dataKelompok = $request->get('id_kelompok', null);
        $dataKelas = $request->get('kelas');

        $query = tblCppdb::select([
            DB::raw('AVG(cppdb.nilai1) AS average_nilai1'),
            DB::raw('AVG(cppdb.nilai2) AS average_nilai2'),
            DB::raw('AVG(cppdb.nilai3) AS average_nilai3'),
            DB::raw('AVG(cppdb.nilai4) AS average_nilai4'),
            DB::raw('AVG(cppdb.nilai5) AS average_nilai5'),
            DB::raw('AVG(cppdb.nilai6) AS average_nilai6'),
            DB::raw('AVG(cppdb.nilai7) AS average_nilai7'),
            DB::raw('AVG(cppdb.nilai8) AS average_nilai8'),
            DB::raw('AVG(cppdb.nilai9) AS average_nilai9'),
            DB::raw('AVG(cppdb.nilai10) AS average_nilai10'),
            DB::raw('AVG(
                CASE 
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'F\' THEN 50
                    ELSE 0
                END
            ) AS average_nilai12'),

            DB::raw('AVG(
                CASE 
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'F\' THEN 50
                    ELSE 0
                END
            ) AS average_nilai13'),

            DB::raw('AVG(
                CASE 
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'F\' THEN 50
                    ELSE 0
                END
            ) AS average_nilai14'),

            DB::raw('AVG(
                CASE 
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'F\' THEN 50
                    ELSE 0
                END
            ) AS average_nilai15'),

            DB::raw('AVG(
                CASE 
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'F\' THEN 50
                    ELSE 0
                END
            ) AS average_nilai16'),
            DB::raw('AVG(cppdb.nilai_presensi_1) AS average_nilai_presensi_1'),
            DB::raw('AVG(cppdb.nilai_presensi_2) AS average_nilai_presensi_2'),
            DB::raw('AVG(cppdb.nilai_presensi_3) AS average_nilai_presensi_3'),
            DB::raw('(
                AVG(cppdb.nilai1) + AVG(cppdb.nilai2) + AVG(cppdb.nilai3) + AVG(cppdb.nilai4) + AVG(cppdb.nilai5) + 
                AVG(cppdb.nilai6) + AVG(cppdb.nilai7) + AVG(cppdb.nilai8) + AVG(cppdb.nilai9) + AVG(cppdb.nilai10) +
                AVG(CASE 
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'F\' THEN 50
                    ELSE 0
                END) +
                AVG(CASE 
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'F\' THEN 50
                    ELSE 0
                END) +
                AVG(CASE 
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'F\' THEN 50
                    ELSE 0
                END) +
                AVG(CASE 
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'F\' THEN 50
                    ELSE 0
                END) +
                AVG(CASE 
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'F\' THEN 50
                    ELSE 0
                END)
            ) / 18 AS total_nilai_average'),
        ])
            ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
            ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
            ->leftJoin('data_peserta', 'cppdb.id_peserta', '=', 'data_peserta.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->where('cppdb.status_naik_kelas', '=', 1)
            ->where('kalender_pendidikan.id', '=', $dataThnAkademik)
            ->where('tabel_daerah.id', '=', $dataDaerah)
            ->where('kelas_peserta_didik.id', '=', $dataKelas);

        if (!is_null($dataDaerah)) {
            $query->where('tabel_daerah.id', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $query->where('tabel_desa.id', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $query->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        $result = $query->first();

        if (!$result) {
            return response()->json([
                'message' => 'Data nilai tidak ditemukan.',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'data_rapor' => $result,
            'success' => true,
        ], 200);
    }

    public function data_dashboard_ranking(Request $request)
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
            'id_thn_akademik' => 'required|numeric',
            'id_daerah' => 'required|numeric',
            'kelas' => 'required|numeric',
        ], $customMessages);

        $dataThnAkademik = $request->get('id_thn_akademik');
        $dataDaerah = $request->get('id_daerah', null);
        $dataDesa = $request->get('id_desa', null);
        $dataKelompok = $request->get('id_kelompok', null);
        $dataKelas = $request->get('kelas');

        // Ambil data peserta berdasarkan parameter yang diberikan
        $query = tblCppdb::select([
            'cppdb.id',
            'data_peserta.nama_lengkap AS nama_peserta',
            'kelas_peserta_didik.nama_kelas',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            DB::raw('(
                cppdb.nilai1 + cppdb.nilai2 + cppdb.nilai3 + cppdb.nilai4 + cppdb.nilai5 + 
                cppdb.nilai6 + cppdb.nilai7 + cppdb.nilai8 + cppdb.nilai9 + cppdb.nilai10 +
                CASE 
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'F\' THEN 50
                    ELSE 0
                END - cppdb.nilai_presensi_1 - cppdb.nilai_presensi_2 - cppdb.nilai_presensi_3
            ) AS total_nilai'),
            DB::raw('(
                cppdb.nilai1 + cppdb.nilai2 + cppdb.nilai3 + cppdb.nilai4 + cppdb.nilai5 + 
                cppdb.nilai6 + cppdb.nilai7 + cppdb.nilai8 + cppdb.nilai9 + cppdb.nilai10 +
                CASE 
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'F\' THEN 50
                    ELSE 0
                END - cppdb.nilai_presensi_1 - cppdb.nilai_presensi_2 - cppdb.nilai_presensi_3
            ) / 18 AS rata_rata_nilai'),
        ])
            ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
            ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
            ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
            ->leftJoin('data_peserta', 'cppdb.id_peserta', '=', 'data_peserta.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->where('cppdb.status_naik_kelas', '=', 1)
            ->where('kalender_pendidikan.id', '=', $dataThnAkademik)
            ->where('tabel_daerah.id', '=', $dataDaerah)
            ->where('kelas_peserta_didik.id', '=', $dataKelas);

        if (!is_null($dataDaerah)) {
            $query->where('tabel_daerah.id', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $query->where('tabel_desa.id', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $query->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        // Execute the query and get the results
        $table_calon_ppdb = $query->get();

        // Handle empty results
        if ($table_calon_ppdb->isEmpty()) {
            return response()->json([
                'message' => 'Data tidak ditemukan.',
                'success' => false,
            ], 404);
        }

        // Tambahkan ranking berdasarkan total nilai dalam kelas yang sama dan tahun akademik yang sama
        $table_calon_ppdb = $table_calon_ppdb->sortByDesc('total_nilai')->values();
        $table_calon_ppdb->each(function ($item, $index) {
            $item->ranking = $index + 1;
        });

        return response()->json([
            'data_dashboard_ranking' => $table_calon_ppdb,
            'success' => true,
        ]);
    }

    public function list(Request $request)
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
            'id_thn_akademik' => 'required|numeric',
            'id_daerah' => 'required|numeric',
            'id_desa' => 'required|numeric',
            'id_kelompok' => 'required|numeric',
            'kelas' => 'required|numeric',
        ], $customMessages);

        $perPage = $request->get('per-page', 10);
        $dataThnAkademik = $request->get('id_thn_akademik');
        $dataDaerah = $request->get('id_daerah');
        $dataDesa = $request->get('id_desa');
        $dataKelompok = $request->get('id_kelompok', null);
        $dataKelas = $request->get('kelas');
        $status = $request->get('status', null);

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
            ->leftJoin('data_peserta', 'cppdb.id_peserta', '=', 'data_peserta.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
            ->leftJoin('users', 'cppdb.id_petugas', '=', 'users.id');

        if (!is_null($dataThnAkademik)) {
            $model->where('kalender_pendidikan.id', '=', $dataThnAkademik);
        }

        if (!is_null($dataDaerah)) {
            $model->where('tabel_daerah.id', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $model->where('tabel_desa.id', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $model->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        if (!is_null($dataKelas)) {
            $model->where('kelas_peserta_didik.id', '=', $dataKelas);
        }

        if (!is_null($status)) {
            $model->where('cppdb.status_naik_kelas', '=', $status);
        }

        // Apply orderByRaw before executing the query
        $model->orderByRaw('cppdb.created_at IS NULL, cppdb.created_at DESC');

        $pelaporan_evaluasi_generus = $model->paginate($perPage);
        $pelaporan_evaluasi_generus->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Data Tersedia',
            'data_pelaporan' => $pelaporan_evaluasi_generus,
            'success' => true,
        ], 200);
    }

    public function getLaporanEvaluasi(Request $request)
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
            'id_thn_akademik' => 'required|numeric',
            'id_daerah' => 'required|numeric',
            'id_desa' => 'required|numeric',
            'id_kelompok' => 'numeric',
            'kelas' => 'required|numeric',
        ], $customMessages);

        $dataThnAkademik = $request->get('id_thn_akademik');
        $dataDaerah = $request->get('id_daerah');
        $dataDesa = $request->get('id_desa');
        $dataKelompok = $request->get('id_kelompok', null);
        $dataKelas = $request->get('kelas');
        $status = $request->get('status', null);

        // Ambil data peserta berdasarkan parameter yang diberikan
        $query = tblCppdb::select([
            'cppdb.id',
            'cppdb.id_thn_akademik',
            'cppdb.id_kelas',
            'cppdb.id_pengajar',
            'cppdb.id_peserta',
            'kalender_pendidikan.tahun_pelajaran AS tahun_akademik',
            'kalender_pendidikan.semester_pelajaran AS semester_akademik',
            'kelas_peserta_didik.nama_kelas',
            'data_peserta.nama_lengkap AS nama_peserta',
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
            'cppdb.nilai12',
            'cppdb.nilai13',
            'cppdb.nilai14',
            'cppdb.nilai15',
            'cppdb.nilai16',
            'cppdb.nilai_presensi_1',
            'cppdb.nilai_presensi_2',
            'cppdb.nilai_presensi_3',
            'cppdb.status_naik_kelas',
            DB::raw('(
                cppdb.nilai1 + cppdb.nilai2 + cppdb.nilai3 + cppdb.nilai4 + cppdb.nilai5 + 
                cppdb.nilai6 + cppdb.nilai7 + cppdb.nilai8 + cppdb.nilai9 + cppdb.nilai10 +
                CASE 
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai12, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai13, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai14, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai15, \'F\') = \'F\' THEN 50
                    ELSE 0
                END +
                CASE 
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'A\' THEN 100
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'B\' THEN 90
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'C\' THEN 80
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'D\' THEN 70
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'E\' THEN 60
                    WHEN COALESCE(cppdb.nilai16, \'F\') = \'F\' THEN 50
                    ELSE 0
                END - cppdb.nilai_presensi_1 - cppdb.nilai_presensi_2 - cppdb.nilai_presensi_3
            ) AS total_nilai'),
        ])
            ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
            ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
            ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
            ->leftJoin('data_peserta', 'cppdb.id_peserta', '=', 'data_peserta.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->where('kalender_pendidikan.id', '=', $dataThnAkademik)
            ->where('tabel_daerah.id', '=', $dataDaerah)
            ->where('tabel_desa.id', '=', $dataDesa)
            ->where('kelas_peserta_didik.id', '=', $dataKelas);

        if (!is_null($dataKelompok)) {
            $query->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        if (!is_null($status)) {
            $query->where('cppdb.status_naik_kelas', '=', $status);
        }

        // Execute the query and get the results
        $table_calon_ppdb = $query->get();

        // Tambahkan ranking berdasarkan total nilai dalam kelas yang sama dan tahun akademik yang sama
        $table_calon_ppdb = $table_calon_ppdb->sortByDesc('total_nilai')->values();
        $table_calon_ppdb->each(function ($item, $index) {
            $item->ranking = $index + 1;
        });

        return response()->json([
            'data_rapor' => $table_calon_ppdb,
            'success' => true,
        ]);
    }
}
