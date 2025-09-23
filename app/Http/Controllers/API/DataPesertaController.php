<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\dataSensusPeserta;
use App\Models\tblCppdb;
use App\Models\tblPekerjaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use App\Models\logs;
use App\Models\presensi;
use App\Models\presensiKegiatan;

class DataPesertaController extends Controller
{
    public function dashboard_sensus(Request $request)
    {
        $modelDataDaerah = $request->get('data_daerah');
        $modelDataDesa = $request->get('data_desa');
        $modelDataKelompok = $request->get('data_kelompok');
        $jenisData = $request->get('jenis_data');

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
            'data_daerah' => 'required|string',
            'data_desa' => 'nullable',
            'data_kelompok' => 'nullable',
            'jenis_data' => 'nullable',
        ], $customMessages);

        $current_timestamp = Carbon::now()->toDateTimeString();

        $query = dataSensusPeserta::select([
            DB::raw('COALESCE(MONTH(data_peserta.created_at), 1) AS month'),
            DB::raw('COUNT(*) AS total_data'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) <= 13 THEN 1
                    ELSE 0
                END) AS total_pra_remaja'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) > 13
                    AND TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) <= 16 THEN 1
                    ELSE 0
                END) AS total_remaja'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) > 16 THEN 1
                    ELSE 0
                END) AS total_muda_mudi_usia_nikah'),
            DB::raw('SUM(CASE WHEN data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1 ELSE 0 END) AS total_laki'),
            DB::raw('SUM(CASE WHEN data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1 ELSE 0 END) AS total_perempuan'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) <= 13
                    AND data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1
                    ELSE 0
                END) AS total_pra_remaja_laki'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) <= 13
                    AND data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1
                    ELSE 0
                END) AS total_pra_remaja_perempuan'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) > 13
                    AND TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) <= 16
                    AND data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1
                    ELSE 0
                END) AS total_remaja_laki'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) > 13
                    AND TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) <= 16
                    AND data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1
                    ELSE 0
                END) AS total_remaja_perempuan'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) > 16
                    AND data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1
                    ELSE 0
                END) AS total_muda_mudi_usia_nikah_laki'),
            DB::raw('
                SUM(CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) > 16
                    AND data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1
                    ELSE 0
                END) AS total_muda_mudi_usia_nikah_perempuan'),
        ])
            ->leftJoin('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'))
            ->leftJoin('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'))
            ->leftJoin('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'))
            ->leftJoin('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'))
            ->groupBy('month')
            ->orderBy('month');

        if (!is_null($modelDataDaerah)) {
            $query->where('tabel_daerah.id', '=', $modelDataDaerah);
        }

        if (!is_null($modelDataDesa)) {
            $query->where('tabel_desa.id', '=', $modelDataDesa);
        }

        if (!is_null($modelDataKelompok)) {
            $query->where('tabel_kelompok.id', '=', $modelDataKelompok);
        }

        if (!is_null($modelDataKelompok)) {
            $query->where('data_peserta.jenis_data', '=', $jenisData);
        }

        $results = $query->get();

        $total_data_keseluruhan = 0;
        $total_laki_keseluruhan = 0;
        $total_perempuan_keseluruhan = 0;

        $total_pra_remaja_keseluruhan = 0;
        $total_laki_pra_remaja_keseluruhan = 0;
        $total_perempuan_pra_remaja_keseluruhan = 0;

        $total_remaja_keseluruhan = 0;
        $total_laki_remaja_keseluruhan = 0;
        $total_perempuan_remaja_keseluruhan = 0;

        $total_muda_mudi_usia_nikah_keseluruhan = 0;
        $total_laki_muda_mudi_usia_nikah_keseluruhan = 0;
        $total_perempuan_muda_mudi_usia_nikah_keseluruhan = 0;

        foreach ($results as $result) {
            // Tambahkan nilai ke total keseluruhan
            $total_data_keseluruhan += $result->total_data;
            $total_laki_keseluruhan += $result->total_laki;
            $total_perempuan_keseluruhan += $result->total_perempuan;

            $total_pra_remaja_keseluruhan += $result->total_pra_remaja;
            $total_laki_pra_remaja_keseluruhan += $result->total_pra_remaja_laki;
            $total_perempuan_pra_remaja_keseluruhan += $result->total_pra_remaja_perempuan;

            $total_remaja_keseluruhan += $result->total_remaja;
            $total_laki_remaja_keseluruhan += $result->total_remaja_laki;
            $total_perempuan_remaja_keseluruhan += $result->total_remaja_perempuan;

            $total_muda_mudi_usia_nikah_keseluruhan += $result->total_muda_mudi_usia_nikah;
            $total_laki_muda_mudi_usia_nikah_keseluruhan += $result->total_muda_mudi_usia_nikah_laki;
            $total_perempuan_muda_mudi_usia_nikah_keseluruhan += $result->total_muda_mudi_usia_nikah_perempuan;
        }

        return response()->json([
            'message' => 'Data ditemukan',
            'total_data_keseluruhan' => $total_data_keseluruhan,
            'total_laki_keseluruhan' => $total_laki_keseluruhan,
            'total_perempuan_keseluruhan' => $total_perempuan_keseluruhan,

            'total_pra_remaja_keseluruhan' => $total_pra_remaja_keseluruhan,
            'total_pra_remaja_laki_keseluruhan' => $total_laki_pra_remaja_keseluruhan,
            'total_pra_remaja_perempuan_keseluruhan' => $total_perempuan_pra_remaja_keseluruhan,

            'total_remaja_keseluruhan' => $total_remaja_keseluruhan,
            'total_remaja_laki_keseluruhan' => $total_laki_remaja_keseluruhan,
            'total_remaja_perempuan_keseluruhan' => $total_perempuan_remaja_keseluruhan,

            'total_muda_mudi_usia_nikah_keseluruhan' => $total_muda_mudi_usia_nikah_keseluruhan,
            'total_muda_mudi_usia_nikah_laki_keseluruhan' => $total_laki_muda_mudi_usia_nikah_keseluruhan,
            'total_muda_mudi_usia_nikah_perempuan_keseluruhan' => $total_perempuan_muda_mudi_usia_nikah_keseluruhan,
            'date_request' => $current_timestamp,
            'success' => true,
        ], 200);
    }

    public function list_pekerjaan()
    {
        $sensus = tblPekerjaan::select(['id', 'nama_pekerjaan'])
            ->groupBy('id', 'nama_pekerjaan')->orderBy('nama_pekerjaan')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_tempat_sambung' => $sensus,
            'success' => true,
        ], 200);
    }

    public function list_users_sensus()
    {
        $users = User::select(['id', 'nama_lengkap'])
            ->whereIn('role_id', [2, 4])
            ->orderBy('nama_lengkap')
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data' => $users,
            'success' => true,
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $statusPernikahan = $request->get('status_pernikahan', null);
        $statusSambung = $request->get('status_sambung', null);
        $statusAtletAsad = $request->get('status_atlet_asad', null);
        $jenisKelamin = $request->get('jenis_kelamin', null);
        $jenisData = $request->get('jenis_data', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.kode_cari_data',
            'data_peserta.nama_lengkap',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.status_atlet_asad',
            'data_peserta.tanggal_lahir',
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
            'data_peserta.status_sambung',
            'data_peserta.status_pernikahan',
            'users.nama_lengkap AS user_petugas',
            'data_peserta.jenis_data',
            'data_peserta.created_at',
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'))
            ->join('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'));

        // Apply orderByRaw before executing the query
        $query->orderByRaw('data_peserta.created_at IS NULL, data_peserta.created_at DESC');

        // $query->where('data_peserta.status_pernikahan', '!=', true)
        //     ->where('data_peserta.status_sambung', '!=', 0);

        if (!is_null($statusPernikahan)) {
            $query->where('data_peserta.status_pernikahan', '=', $statusPernikahan);
        }

        if (!is_null($statusSambung)) {
            $query->where('data_peserta.status_sambung', '=', $statusSambung);
        }

        if (!is_null($statusAtletAsad)) {
            $query->where('data_peserta.status_atlet_asad', '=', $statusAtletAsad);
        }

        if (!is_null($jenisKelamin)) {
            $query->where('data_peserta.jenis_kelamin', '=', $jenisKelamin);
        }

        if (!is_null($jenisData)) {
            $query->where('data_peserta.jenis_data', '=', $jenisData);
        }

        if (!empty($keyword) && empty($kolom)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.kode_cari_data', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%')
                    ->orWhere(DB::raw("
                        CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END
                    "), 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.nama_panggilan', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_cari_data') {
                $kolom = 'data_peserta.kode_cari_data';
            } else {
                $kolom = 'data_peserta.kode_cari_data';
            }

            $query->where($kolom, 'LIKE', '%' . $keyword . '%');
        }

        $sensus = $query->paginate($perPage);

        $sensus->appends([
            'per-page' => $perPage,
        ]);

        return response()->json([
            'message' => 'Data Ditemukan',
            'data_sensus' => $sensus,
            'success' => true,
        ], 200);
    }

    public function listByPtgs(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $dataDaerah = $request->get('data-daerah', $user->role_daerah);
        $dataDesa = $request->get('data-desa', $user->role_desa);
        $dataKelompok = $request->get('data-kelompok', $user->role_kelompok);
        $statusPernikahan = $request->get('status_pernikahan', null);
        $statusSambung = $request->get('status_sambung', null);
        $statusAtletAsad = $request->get('status_atlet_asad', null);
        $jenisKelamin = $request->get('jenis_kelamin', null);
        $jenisData = $request->get('jenis_data', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = dataSensusPeserta::select([
            'data_peserta.id',
            DB::raw('CONCAT(SUBSTRING(data_peserta.kode_cari_data FROM 1 FOR 2), \'****\', SUBSTRING(data_peserta.kode_cari_data FROM 7 FOR 4)) AS kode_cari_data'),
            'data_peserta.nama_lengkap',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.status_atlet_asad',
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
            'data_peserta.status_sambung',
            'data_peserta.status_pernikahan',
            'users.nama_lengkap AS user_petugas',
            'data_peserta.jenis_data',
            'data_peserta.created_at',
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'))
            ->join('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'));

        if (!is_null($dataDaerah)) {
            $query->where('tabel_daerah.id', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $query->where('tabel_desa.id', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $query->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        if (!is_null($statusPernikahan)) {
            $query->where('data_peserta.status_pernikahan', '=', $statusPernikahan);
        }

        if (!is_null($statusSambung)) {
            $query->where('data_peserta.status_sambung', '=', $statusSambung);
        }

        if (!is_null($statusAtletAsad)) {
            $query->where('data_peserta.status_atlet_asad', '=', $statusAtletAsad);
        }

        if (!is_null($jenisKelamin)) {
            $query->where('data_peserta.jenis_kelamin', '=', $jenisKelamin);
        }

        if (!is_null($jenisData)) {
            $query->where('data_peserta.jenis_data', '=', $jenisData);
        }

        // Apply orderByRaw before executing the query
        $query->orderByRaw('data_peserta.created_at IS NULL, data_peserta.created_at DESC');

        // $query->where('data_peserta.status_pernikahan', '!=', true)
        //     ->where('data_peserta.status_sambung', '!=', 0);

        if (!empty($keyword) && empty($kolom)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%')
                    ->orWhere(DB::raw("
                    CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                    WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                    ELSE 'Tidak dalam rentang usia'
                END
                "), 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.nama_panggilan', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_cari_data') {
                $kolom = 'data_peserta.kode_cari_data';
            } else {
                $kolom = 'data_peserta.kode_cari_data';
            }

            $query->where($kolom, 'LIKE', '%' . $keyword . '%');
        }

        $sensus = $query->paginate($perPage);

        $sensus->appends([
            'per-page' => $perPage,
        ]);

        return response()->json([
            'message' => 'Sukses',
            'data_sensus' => $sensus,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
        $sensus = User::find($request->user_id);

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
            'nama_lengkap' => 'required|string|unique:data_peserta',
            'nama_panggilan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'alamat' => 'required|string',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'no_telepon' => 'required|string|digits_between:8,13',
            'nama_ayah' => 'required|string',
            'nama_ibu' => 'required|string',
            'hoby' => 'required|string',
            'pekerjaan' => 'required|integer',
            'usia_menikah' => 'nullable',
            'kriteria_pasangan' => 'nullable',
            'status_atlet_asad' => 'required|integer',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'required|integer|digits_between:1,5',
            'tmpt_kelompok' => 'required|integer|digits_between:1,5',
            'img' => 'nullable|image|mimes:jpg,png|max:5120',
            'user_id' => 'required|integer',
        ], $customMessages);

        $tabel_sensus = new dataSensusPeserta();

        $tanggalSekarang = Carbon::now();
        $prefix = 'SEN';

        do {
            $kodeUnik = $prefix . $tanggalSekarang->format('ymdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        } while (\App\Models\dataSensusPeserta::where('kode_cari_data', $kodeUnik)->exists());

        $tabel_sensus->kode_cari_data = $kodeUnik;
        $tabel_sensus->nama_lengkap = $request->nama_lengkap;
        $tabel_sensus->nama_panggilan = ucwords(strtolower($request->nama_panggilan));
        $tabel_sensus->tempat_lahir = ucwords(strtolower($request->tempat_lahir));
        $tabel_sensus->tanggal_lahir = $request->tanggal_lahir;
        $tabel_sensus->alamat = ucwords(strtolower($request->alamat));
        $tabel_sensus->jenis_kelamin = $request->jenis_kelamin;
        $tabel_sensus->no_telepon = $request->no_telepon;
        $tabel_sensus->nama_ayah = $request->nama_ayah;
        $tabel_sensus->nama_ibu = $request->nama_ibu;
        $tabel_sensus->hoby = $request->hoby;
        $tabel_sensus->pekerjaan = $request->pekerjaan;
        $tabel_sensus->usia_menikah = $request->usia_menikah;
        $tabel_sensus->kriteria_pasangan = $request->kriteria_pasangan;
        $tabel_sensus->tmpt_daerah = $request->tmpt_daerah;
        $tabel_sensus->tmpt_desa = $request->tmpt_desa;
        $tabel_sensus->tmpt_kelompok = $request->tmpt_kelompok;
        $tabel_sensus->status_sambung = 1;
        $tabel_sensus->status_pernikahan = 0;
        $tabel_sensus->jenis_data = "SENSUS";
        $tabel_sensus->img = $request->img;
        $tabel_sensus->status_atlet_asad = $request->status_atlet_asad;
        $tabel_sensus->user_id = $request->user_id;

        if ($request->hasFile('img')) {
            $foto = $request->file('img'); // Get the uploaded file

            // Generate a unique filename
            $namaFile = Str::slug($tabel_sensus->kode_cari_data) . '.' . $foto->getClientOriginalExtension();

            // Save the file to the 'public/images/sensus' directory
            $path = $foto->storeAs('public/images/sensus', $namaFile);

            // Update the database record
            $tabel_sensus->img = $path;
        } else {
            $tabel_sensus->img = null; // Handle cases where no file is uploaded
        }

        if (!$tabel_daerah || !$tabel_desa || !$tabel_kelompok || !$sensus) {
            return response()->json([
                'message' => 'Validasi lokasi atau user gagal',
                'success' => false,
                'errors' => [
                    'tmpt_daerah' => !$tabel_daerah ? 'Daerah tidak ditemukan' : null,
                    'tmpt_desa' => !$tabel_desa ? 'Desa tidak ditemukan' : null,
                    'tmpt_kelompok' => !$tabel_kelompok ? 'Kelompok tidak ditemukan' : null,
                    'user_id' => !$sensus ? 'User tidak ditemukan' : null,
                ]
            ], 404);
        }

        try {

            $tabel_sensus->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Create Data Sensus - [' . $tabel_sensus->id . '] - [' . $tabel_sensus->nama_lengkap . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ];
            logs::create($logAccount);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data sensus' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($tabel_sensus->created_at, $tabel_sensus->updated_at);

        return response()->json([
            'message' => 'Data sensus berhasil ditambahkan',
            'data_sensus' => $tabel_sensus,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $sensus = dataSensusPeserta::select([
            'data_peserta.id',
            // DB::raw('CONCAT(SUBSTRING(data_peserta.kode_cari_data FROM 1 FOR 2), \'****\', SUBSTRING(data_peserta.kode_cari_data FROM 7 FOR 4)) AS kode_cari_data'),
            'data_peserta.kode_cari_data',
            'data_peserta.nama_lengkap',
            'data_peserta.nama_panggilan',
            'data_peserta.tempat_lahir',
            'data_peserta.tanggal_lahir',
            'data_peserta.alamat',
            DB::raw('TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) AS usia'),
            'data_peserta.jenis_kelamin',
            'data_peserta.no_telepon',
            'data_peserta.nama_ayah',
            'data_peserta.nama_ibu',
            'data_peserta.hoby',
            'tbl_pekerjaan.id AS id_pekerjaan',
            'tbl_pekerjaan.nama_pekerjaan AS pekerjaan',
            'data_peserta.usia_menikah',
            'data_peserta.kriteria_pasangan',
            'data_peserta.status_pernikahan',
            'data_peserta.status_sambung',
            'data_peserta.user_id',
            'tabel_daerah.id as id_daerah',
            'tabel_daerah.nama_daerah',
            'tabel_desa.id as id_desa',
            'tabel_desa.nama_desa',
            'tabel_kelompok.id as id_kelompok',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.jenis_data',
            'data_peserta.status_atlet_asad',
            'users.id AS user_petugas',
            'data_peserta.img',
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
        ])->join('tabel_daerah', function ($join) {
            $join->on('tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'));
        })->join('tabel_desa', function ($join) {
            $join->on('tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'));
        })->join('tabel_kelompok', function ($join) {
            $join->on('tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'));
        })->join('tbl_pekerjaan', function ($join) {
            $join->on('tbl_pekerjaan.id', '=', DB::raw('CAST(data_peserta.pekerjaan AS UNSIGNED)'));
        })->join('users', function ($join) {
            $join->on('users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'));
        })->where('data_peserta.id', '=', $request->id)->first();

        if ($sensus) {
            // Generate the correct URL for the image
            $sensus->img_url = $sensus->img
                ? asset('storage/' . str_replace('public/', '', $sensus->img))
                : null;

            unset($sensus->created_at, $sensus->updated_at);

            return response()->json([
                'message' => 'Data Peserta Ditemukan',
                'data_sensus' => $sensus,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Sensus tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update(Request $request)
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
            'nama_lengkap' => 'sometimes|required|string|unique:data_peserta,nama_lengkap,' . $request->id . ',id',
            'nama_panggilan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'alamat' => 'required|string',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'no_telepon' => 'sometimes|nullable',
            'nama_ayah' => 'required|string',
            'nama_ibu' => 'required|string',
            'hoby' => 'required|string',
            'pekerjaan' => 'required|integer',
            'usia_menikah' => 'sometimes|nullable',
            'kriteria_pasangan' => 'sometimes|nullable',
            'status_sambung' => 'integer',
            'status_pernikahan' => 'boolean',
            'status_atlet_asad' => 'required|integer',
            'jenis_data' => 'sometimes|required|string',
            'user_id' => 'sometimes|nullable',
        ], $customMessages);

        $sensus = dataSensusPeserta::where('id', '=', $request->id)
            ->first();

        if (!$sensus) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
                'success' => false,
            ], 404);
        }

        try {

            $originalData = $sensus->getOriginal();

            $sensus->fill([
                'nama_lengkap' => $request->nama_lengkap,
                'nama_panggilan' => ucwords(strtolower($request->nama_panggilan)),
                'tempat_lahir' => ucwords(strtolower($request->tempat_lahir)),
                'tanggal_lahir' => $request->tanggal_lahir,
                'alamat' => ucwords(strtolower($request->alamat)),
                'jenis_kelamin' => $request->jenis_kelamin,
                'no_telepon' => $request->no_telepon,
                'nama_ayah' => $request->nama_ayah,
                'nama_ibu' => $request->nama_ibu,
                'hoby' => $request->hoby,
                'pekerjaan' => $request->pekerjaan,
                'usia_menikah' => $request->usia_menikah,
                'kriteria_pasangan' => $request->kriteria_pasangan,
                'status_sambung' => $request->status_sambung,
                'status_pernikahan' => $request->status_pernikahan,
                'status_atlet_asad' => $request->status_atlet_asad,
                'jenis_data' => $request->jenis_data,
                'tmpt_daerah' => $request->tmpt_daerah,
                'tmpt_desa' => $request->tmpt_desa,
                'tmpt_kelompok' => $request->tmpt_kelompok,
                'user_id' => $request->user_id,
                'img' => $request->img,
            ]);

            if ($request->hasFile('img')) {
                $request->validate([
                    'img' => 'nullable|image|mimes:jpg,png|max:5120',
                ], $customMessages);
                $oldImgPath = storage_path('public/images/sensus/' . $sensus->img);

                // Hapus file lama jika ada
                if (file_exists($oldImgPath) && $sensus->img) {
                    unlink($oldImgPath);
                }

                // Save the file to the 'public/images/sensus' directory
                $newImg = $request->file('img');
                $namaFile = Str::slug($sensus->nama_lengkap) . '.' . $newImg->getClientOriginalExtension();
                $path = $newImg->storeAs('public/images/sensus', $namaFile);
                $sensus->img = $path;
            }

            // Cek apakah jenis_data berubah
            if (array_key_exists('jenis_data', $sensus->getDirty())) {
                $prefix = $sensus->jenis_data === 'SENSUS' ? 'SEN' : 'KBM';

                // Generate kode unik 16 digit
                do {
                    $kodeBaru = $prefix . date('ymdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                } while (dataSensusPeserta::where('kode_cari_data', $kodeBaru)->exists());

                $sensus->kode_cari_data = $kodeBaru;
            }

            $updatedFields = [];
            foreach ($sensus->getDirty() as $field => $newValue) {
                $oldValue = $originalData[$field] ?? null; // Ambil nilai lama
                $updatedFields[] = "$field: [$oldValue] -> [$newValue]";
            }

            $sensus->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Update Data Sensus - [' . $sensus->id . '] - [' . $sensus->nama_lengkap . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
                'updated_fields' => json_encode($updatedFields), // Simpan sebagai JSON
            ];
            logs::create($logAccount);

            return response()->json([
                'message' => 'Data Sensus berhasil diupdate',
                'data_sensus' => $sensus,
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal mengupdate data sensus' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $sensus = dataSensusPeserta::where('id', '=', $request->id)
            ->first();

        if (!empty($sensus)) {
            $existsInCppdb = tblCppdb::where('id_peserta', $request->id)->exists();
            $existsInPresensi = presensi::where('id_peserta', $request->id)->exists();

            if ($existsInCppdb && $existsInPresensi) {
                return response()->json([
                    'message' => 'Data sensus tidak dapat dihapus karena sudah terdaftar dan digunakan di tabel lain',
                    'success' => false,
                ], 409);
            }

            try {
                // Hapus file gambar jika ada
                if (!empty($sensus->img)) {
                    $filePath = storage_path('app/' . $sensus->img); // Path lengkap file
                    if (file_exists($filePath)) {
                        unlink($filePath); // Hapus file dari folder
                    }
                }

                $deletedData = $sensus->toArray();

                // Lanjutkan untuk menghapus data Peserta Didik
                $sensus->delete();

                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Delete Data Sensus - [' . $deletedData['id'] . '] - [' . $deletedData['nama_lengkap'] . ']',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    'engine_agent' => $request->header('user-agent'),
                ];
                logs::create($logAccount);

                return response()->json([
                    'message' => 'Data Peserta Didik berhasil dihapus beserta file terkait',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                // Kembalikan respons kesalahan jika penghapusan gagal
                return response()->json([
                    'message' => 'Gagal menghapus Data: ' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function presensi_peserta(Request $request)
    {
        $request->validate([
            'id_peserta' => 'required|numeric',
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
        ]);

        // Cari data peserta
        $peserta = dataSensusPeserta::find($request->id_peserta);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan',
            ], 404);
        }

        // Ambil hanya kegiatan dengan category = MUMI
        $kegiatanList = presensiKegiatan::where('category', 'MUMI')->get();

        if ($kegiatanList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada kegiatan kategori MUMI yang terdaftar',
            ], 404);
        }

        $statusKeseluruhan = 'HADIR';
        $detailPresensi = [];

        foreach ($kegiatanList as $kegiatan) {
            $presensi = Presensi::where('id_peserta', $peserta->id)
                ->where('id_kegiatan', $kegiatan->id)
                ->first();

            if ($presensi) {
                $detailPresensi[] = [
                    'id_kegiatan' => $kegiatan->id,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan ?? null,
                    'status_presensi' => $presensi->status_presensi,
                    'keterangan' => $presensi->keterangan,
                    'waktu_presensi' => $presensi->waktu_presensi,
                ];
            } else {
                $detailPresensi[] = [
                    'id_kegiatan' => $kegiatan->id,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan ?? null,
                    'status_presensi' => 'ALFA',
                    'keterangan' => null,
                    'waktu_presensi' => null,
                ];
                $statusKeseluruhan = 'ALFA'; // kalau ada 1 saja yg alfa langsung terindikasi alfa
            }
        }

        return response()->json([
            'success' => true,
            'message' => $statusKeseluruhan === 'ALFA'
                ? 'Peserta terindikasi ALFA pada salah satu kegiatan'
                : 'Peserta hadir di semua kegiatan',
            'data' => [
                'id_peserta' => $peserta->kode_cari_data,
                'nama_lengkap' => $peserta->nama_lengkap,
                'status_keseluruhan' => $statusKeseluruhan,
                'keterangan' => $statusKeseluruhan === 'ALFA'
                    ? 'Peserta terindikasi ALFA pada salah satu kegiatan'
                    : 'Peserta hadir di semua kegiatan',
                'detail_presensi' => $detailPresensi,
            ]
        ], 200);
    }

    public function sensus_report_pdf(Request $request)
    {
        $modelDataDaerah = $request->get('data_daerah');
        $modelDataDesa = $request->get('data_desa');
        $modelDataKelompok = $request->get('data_kelompok');
        $statusPernikahan = $request->get('status_pernikahan', null);
        $statusSambung = $request->get('status_sambung', null);
        $statusAtletAsad = $request->get('status_atlet_asad', null);
        $jenisKelamin = $request->get('jenis_kelamin', null);
        $jenisData = $request->get('jenis_data', null);

        // Define the query with the necessary joins and selections
        $query = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.nama_lengkap',
            'data_peserta.tempat_lahir',
            'data_peserta.tanggal_lahir',
            'data_peserta.jenis_kelamin',
            DB::raw('TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) AS usia'),
            'data_peserta.nama_ayah',
            'data_peserta.nama_ibu',
            'data_peserta.hoby',
            'tbl_pekerjaan.nama_pekerjaan AS pekerjaan',
            DB::raw("CASE
        WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
        WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
        WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
        WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
        WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
        ELSE 'Tidak dalam rentang usia'
        END AS status_kelas"),
            DB::raw("CASE 
            WHEN data_peserta.status_sambung = 1 THEN 'Sambung' 
            WHEN data_peserta.status_sambung = 0 THEN 'Tidak Sambung' 
            WHEN data_peserta.status_sambung = 2 THEN 'Pindah Sambung' 
            ELSE 'Tidak Diketahui' 
        END AS status_sambung"),
            DB::raw("CASE 
            WHEN data_peserta.status_pernikahan = 1 THEN 'Sudah Menikah' 
            WHEN data_peserta.status_pernikahan = 0 THEN 'Belum Menikah' 
            ELSE 'Tidak Diketahui' 
        END AS status_pernikahan"),
            'data_peserta.tmpt_kelompok',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.jenis_data',
        ])
            ->join('tbl_pekerjaan', 'tbl_pekerjaan.id', '=', DB::raw('CAST(data_peserta.pekerjaan AS UNSIGNED)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'))
            ->join('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'))
            // Prioritaskan yang status_sambung ≠ 1 (bukan sambung)
            ->orderByRaw("CASE WHEN data_peserta.status_sambung != 1 THEN 0 ELSE 1 END")
            // Prioritaskan yang status_pernikahan ≠ 0 (bukan belum menikah)
            ->orderByRaw("CASE WHEN data_peserta.status_pernikahan != 0 THEN 0 ELSE 1 END")
            ->orderByRaw('data_peserta.created_at IS NULL, data_peserta.created_at DESC');


        if (!is_null($modelDataDaerah)) {
            $query->where('data_peserta.tmpt_daerah', '=', $modelDataDaerah);
        }

        if (!is_null($modelDataDesa)) {
            $query->where('data_peserta.tmpt_desa', '=', $modelDataDesa);
        }

        if (!is_null($modelDataKelompok)) {
            $query->where('data_peserta.tmpt_kelompok', '=', $modelDataKelompok);
        }

        if (!is_null($statusPernikahan)) {
            $query->where('data_peserta.status_pernikahan', '=', $statusPernikahan);
        }

        if (!is_null($statusSambung)) {
            $query->where('data_peserta.status_sambung', '=', $statusSambung);
        }

        if (!is_null($statusAtletAsad)) {
            $query->where('data_peserta.status_atlet_asad', '=', $statusAtletAsad);
        }

        if (!is_null($jenisKelamin)) {
            $query->where('data_peserta.jenis_kelamin', '=', $jenisKelamin);
        }

        if (!is_null($jenisData)) {
            $query->where('data_peserta.jenis_data', '=', $jenisData);
        }

        $statistikQuery = clone $query;

        // Hitung statistik status_sambung
        $statistikSambung = (clone $statistikQuery)
            ->select('data_peserta.status_sambung', DB::raw('COUNT(*) as total'))
            ->groupBy('data_peserta.status_sambung')
            ->pluck('total', 'status_sambung');

        // Hitung statistik status_pernikahan
        $statistikPernikahan = (clone $statistikQuery)
            ->select('data_peserta.status_pernikahan', DB::raw('COUNT(*) as total'))
            ->groupBy('data_peserta.status_pernikahan')
            ->pluck('total', 'status_pernikahan');

        // Fetch data in chunks for better performance
        $data = [];
        $query->chunk(1000, function ($results) use (&$data) {
            foreach ($results as $result) {
                $data[] = $result;
            }
        });

        return response()->json([
            'message' => 'Data Ditemukan',
            'data_sensus_report' => $data,
            'statistik' => [
                'data_sambung' => [
                    'tidak_sambung' => $statistikSambung[0] ?? 0,
                    'sambung' => $statistikSambung[1] ?? 0,
                    'pindah_sambung' => $statistikSambung[2] ?? 0,
                ],
                'data_pernikahan' => [
                    'belum_menikah' => $statistikPernikahan[0] ?? 0,
                    'sudah_menikah' => $statistikPernikahan[1] ?? 0,
                ],
            ],
            'success' => true,

        ], 200);
    }
}
