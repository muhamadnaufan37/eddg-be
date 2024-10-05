<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\tblCppdb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanRaporController extends Controller
{
    public function list(Request $request)
    {
        // Validasi parameter
        $request->validate([
            'id_thn_akademik' => 'required|numeric',
            'id_daerah' => 'required|numeric',
            'id_desa' => 'required|numeric',
            'id_kelompok' => 'required|numeric',
            'kelas' => 'required|numeric',
        ]);

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
            'peserta_didik.nama_lengkap AS nama_peserta',
            'users.nama_lengkap AS nama_petugas',
            'cppdb.status_naik_kelas',
            'cppdb.created_at',
        ])
        ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
        ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
        ->leftJoin('peserta_didik', 'cppdb.id_peserta', '=', 'peserta_didik.id')
        ->leftJoin('tabel_daerah', 'peserta_didik.tmpt_daerah', '=', 'tabel_daerah.id')
        ->leftJoin('tabel_desa', 'peserta_didik.tmpt_desa', '=', 'tabel_desa.id')
        ->leftJoin('tabel_kelompok', 'peserta_didik.tmpt_kelompok', '=', 'tabel_kelompok.id')
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
        // Validasi parameter
        $request->validate([
            'id_thn_akademik' => 'required|numeric',
            'id_daerah' => 'required|numeric',
            'id_desa' => 'required|numeric',
            'id_kelompok' => 'numeric',
            'kelas' => 'required|numeric',
        ]);

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
            'peserta_didik.nama_lengkap AS nama_peserta',
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
                cppdb.nilai6 + cppdb.nilai7 + cppdb.nilai8 + cppdb.nilai9 + cppdb.nilai10
            ) AS total_nilai'),
        ])
        ->leftJoin('kalender_pendidikan', 'cppdb.id_thn_akademik', '=', 'kalender_pendidikan.id')
        ->leftJoin('kelas_peserta_didik', 'cppdb.id_kelas', '=', 'kelas_peserta_didik.id')
        ->leftJoin('pengajar', 'cppdb.id_pengajar', '=', 'pengajar.id')
        ->leftJoin('peserta_didik', 'cppdb.id_peserta', '=', 'peserta_didik.id')
        ->leftJoin('tabel_daerah', 'peserta_didik.tmpt_daerah', '=', 'tabel_daerah.id')
        ->leftJoin('tabel_desa', 'peserta_didik.tmpt_desa', '=', 'tabel_desa.id')
        ->leftJoin('tabel_kelompok', 'peserta_didik.tmpt_kelompok', '=', 'tabel_kelompok.id')
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
