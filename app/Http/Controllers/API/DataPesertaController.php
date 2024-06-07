<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\dataSensusPeserta;
use App\Models\tblPekerjaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataPesertaController extends Controller
{
    public function dashboard_sensus(Request $request)
    {
        $modelDataDaerah = $request->get('data_daerah');
        $modelDataDesa = $request->get('data_desa');
        $modelDataKelompok = $request->get('data_kelompok');

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
            'data_desa' => 'required|string',
            'data_kelompok' => 'nullable',
        ], $customMessages);

        // $currentYear = Carbon::now()->year;
        $currentYear = Carbon::now()->year;
        $current_timestamp = Carbon::now()->toDateTimeString();

        $query = dataSensusPeserta::select([
            DB::raw('COALESCE(EXTRACT(MONTH FROM data_peserta.created_at), 1) AS month'),
            DB::raw('COUNT(*) AS total_data'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 13 THEN 1
                    ELSE 0
                END) AS total_pra_remaja'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 13
                    AND EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 16 THEN 1
                    ELSE 0
                END) AS total_remaja'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 16 THEN 1
                    ELSE 0
                END) AS total_muda_mudi_usia_nikah'),
            DB::raw('SUM(CASE WHEN data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1 ELSE 0 END) AS total_laki'),
            DB::raw('SUM(CASE WHEN data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1 ELSE 0 END) AS total_perempuan'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 13
                    AND data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1
                    ELSE 0
                END) AS total_pra_remaja_laki'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 13
                    AND data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1
                    ELSE 0
                END) AS total_pra_remaja_perempuan'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 13
                    AND EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 16
                    AND data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1
                    ELSE 0
                END) AS total_remaja_laki'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 13
                    AND EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 16
                    AND data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1
                    ELSE 0
                END) AS total_remaja_perempuan'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 16
                    AND data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1
                    ELSE 0
                END) AS total_muda_mudi_usia_nikah_laki'),
            DB::raw('
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 16
                    AND data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1
                    ELSE 0
                END) AS total_muda_mudi_usia_nikah_perempuan'),
        ])
        ->whereYear('data_peserta.created_at', '=', $currentYear)
            ->leftJoin('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS BIGINT)'))
            ->leftJoin('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS BIGINT)'))
            ->leftJoin('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS BIGINT)'))
            ->leftJoin('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS BIGINT)'))
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

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.kode_cari_data',
            'data_peserta.nama_lengkap',
            'data_peserta.nama_panggilan',
            'data_peserta.tempat_lahir',
            'data_peserta.tanggal_lahir',
            'data_peserta.alamat',
            DB::raw('EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) AS usia'),
            'data_peserta.jenis_kelamin',
            'data_peserta.no_telepon',
            'data_peserta.nama_ayah',
            'data_peserta.nama_ibu',
            'data_peserta.hoby',
            'data_peserta.pekerjaan',
            'data_peserta.usia_menikah',
            'data_peserta.kriteria_pasangan',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.status_pernikahan',
            'data_peserta.status_sambung',
            'users.username AS user_petugas',
            'data_peserta.created_at',
            DB::raw("
            CASE
                WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 13 THEN 'Pra-remaja'
                WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 16 THEN 'Remaja'
                ELSE 'Muda - mudi / Usia Nikah'
            END AS status_kelas"),
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS BIGINT)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS BIGINT)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS BIGINT)'))
            ->join('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS BIGINT)'));

        // Apply orderByRaw before executing the query
        $query->orderByRaw('data_peserta.created_at DESC NULLS LAST');

        $query->where('data_peserta.status_pernikahan', '!=', true)
            ->where('data_peserta.status_sambung', '!=', 0);

        if (!empty($keyword) && empty($kolom)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('data_peserta.kode_cari_data', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('tabel_daerah.nama_daerah', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('tabel_desa.nama_desa', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('data_peserta.nama_panggilan', 'ILIKE', '%'.$keyword.'%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_cari_data') {
                $kolom = 'data_peserta.kode_cari_data';
            } else {
                $kolom = 'data_peserta.kode_cari_data';
            }

            $query->where($kolom, 'ILIKE', '%'.$keyword.'%');
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

    public function listByPtgs(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $dataDaerah = $request->get('data-daerah', $user->role_daerah);
        $dataDesa = $request->get('data-desa', $user->role_desa);
        $dataKelompok = $request->get('data-kelompok', $user->role_kelompok);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = dataSensusPeserta::select([
            'data_peserta.id',
            DB::raw('CONCAT(SUBSTRING(data_peserta.kode_cari_data FROM 1 FOR 2), \'****\', SUBSTRING(data_peserta.kode_cari_data FROM 7 FOR 4)) AS kode_cari_data'),
            'data_peserta.nama_lengkap',
            'data_peserta.nama_panggilan',
            'data_peserta.tempat_lahir',
            'data_peserta.tanggal_lahir',
            'data_peserta.alamat',
            DB::raw('EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) AS usia'),
            'data_peserta.jenis_kelamin',
            'data_peserta.no_telepon',
            'data_peserta.nama_ayah',
            'data_peserta.nama_ibu',
            'data_peserta.hoby',
            'data_peserta.pekerjaan',
            'data_peserta.usia_menikah',
            'data_peserta.kriteria_pasangan',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.status_pernikahan',
            'data_peserta.status_sambung',
            'users.username AS user_petugas',
            'data_peserta.created_at',
            DB::raw("
            CASE
                WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 13 THEN 'Pra-remaja'
                WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 16 THEN 'Remaja'
                ELSE 'Muda - mudi / Usia Nikah'
            END AS status_kelas"),
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS BIGINT)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS BIGINT)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS BIGINT)'))
            ->join('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS BIGINT)'));

        if (!is_null($dataDaerah)) {
            $query->where('tabel_daerah.id', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $query->where('tabel_desa.id', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $query->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        // Apply orderByRaw before executing the query
        $query->orderByRaw('data_peserta.created_at DESC NULLS LAST');

        $query->where('data_peserta.status_pernikahan', '!=', true)
            ->where('data_peserta.status_sambung', '!=', 0);

        if (!empty($keyword) && empty($kolom)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('data_peserta.kode_cari_data', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('data_peserta.nama_panggilan', 'ILIKE', '%'.$keyword.'%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_cari_data') {
                $kolom = 'data_peserta.kode_cari_data';
            } else {
                $kolom = 'data_peserta.kode_cari_data';
            }

            $query->where($kolom, 'ILIKE', '%'.$keyword.'%');
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
            'kode_cari_data' => 'unique:data_peserta',
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
            'usia_menikah' => 'nullable|string',
            'kriteria_pasangan' => 'nullable|string',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'required|integer|digits_between:1,5',
            'tmpt_kelompok' => 'required|integer|digits_between:1,5',
            'img_sensus' => 'nullable|image|mimes:png|max:4096',
            'user_id' => 'required|integer',
        ], $customMessages);

        $tabel_sensus = new dataSensusPeserta();

        $tanggalSekarang = Carbon::now();
        $bulan = $tanggalSekarang->format('m'); // Mendapatkan bulan saat ini (format 2 digit)
        $tahun = $tanggalSekarang->format('Y'); // Mendapatkan tahun saat ini (format 4 digit)

        $tabel_sensus->kode_cari_data = $bulan.Str::random(4).$tahun;
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
        $tabel_sensus->user_id = $request->user_id;

        // Menyimpan gambar jika diunggah
        if ($request->hasFile('img_sensus')) {
            // Dapatkan file foto dari permintaan
            $foto = $request->file('img_sensus');

            // Bangun nama file yang disimpan
            $namaFile = Str::slug($tabel_sensus->nama_lengkap).'.'.$foto->getClientOriginalExtension();

            // Simpan gambar ke penyimpanan dengan nama file yang disesuaikan
            $path = $foto->storeAs('public/images/sensus', $namaFile);

            // Anda dapat menyimpan path file ini di database jika diperlukan
            $tabel_sensus->img_sensus = $path;
        }

        try {
            $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
            $tabel_desa = dataDesa::find($request->tmpt_desa);
            $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
            $sensuss = User::find($request->user_id);

            if (!$tabel_daerah) {
                return response()->json([
                    'message' => 'Daerah tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$tabel_desa) {
                return response()->json([
                    'message' => 'Desa tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$tabel_kelompok) {
                return response()->json([
                    'message' => 'Kelompok tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$sensuss) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            $tabel_sensus->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data sensus'.$exception->getMessage(),
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
            DB::raw('CONCAT(SUBSTRING(data_peserta.kode_cari_data FROM 1 FOR 2), \'****\', SUBSTRING(data_peserta.kode_cari_data FROM 7 FOR 4)) AS kode_cari_data'),
            'data_peserta.nama_lengkap',
            'data_peserta.nama_panggilan',
            'data_peserta.tempat_lahir',
            'data_peserta.tanggal_lahir',
            'data_peserta.alamat',
            DB::raw('EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) AS usia'),
            'data_peserta.jenis_kelamin',
            'data_peserta.no_telepon',
            'data_peserta.nama_ayah',
            'data_peserta.nama_ibu',
            'data_peserta.hoby',
            'data_peserta.pekerjaan',
            'data_peserta.usia_menikah',
            'data_peserta.kriteria_pasangan',
            'data_peserta.user_id',
            'tabel_daerah.id as id_daerah',
            'tabel_daerah.nama_daerah',
            'tabel_desa.id as id_desa',
            'tabel_desa.nama_desa',
            'tabel_kelompok.id as id_kelompok',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.status_pernikahan',
            'data_peserta.status_sambung',
            'users.username AS user_petugas',
            DB::raw("
        CASE
            WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 13 THEN 'Pra-remaja'
            WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 16 THEN 'Remaja'
            ELSE 'Muda - mudi / Usia Nikah'
        END AS status_kelas
        "),
        ])->join('tabel_daerah', function ($join) {
            $join->on('tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS BIGINT)'));
        })->join('tabel_desa', function ($join) {
            $join->on('tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS BIGINT)'));
        })->join('tabel_kelompok', function ($join) {
            $join->on('tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS BIGINT)'));
        })->join('users', function ($join) {
            $join->on('users.id', '=', DB::raw('CAST(data_peserta.user_id AS BIGINT)'));
        })->where('data_peserta.id', '=', $request->id)->first();

        if (!empty($sensus)) {
            if ($sensus->img_sensus) {
                $sensus->image_url = asset($sensus->img_sensus);
            } else {
                $sensus->image_url = '';
            }

            return response()->json([
                'message' => 'Sukses',
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
            'nama_lengkap' => 'sometimes|required|string|unique:data_peserta,nama_lengkap,'.$request->id.',id',
            'nama_panggilan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'alamat' => 'required|string',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'no_telepon' => 'sometimes|required|string|digits_between:8,13|unique:data_peserta,no_telepon,'.$request->id.',id',
            'nama_ayah' => 'required|string',
            'nama_ibu' => 'required|string',
            'hoby' => 'required|string',
            'pekerjaan' => 'required|integer',
            'usia_menikah' => 'nullable|string',
            'kriteria_pasangan' => 'nullable|string',
            'status_sambung' => 'integer',
            'status_pernikahan' => 'boolean',
            'user_id' => 'required|integer',
        ], $customMessages);

        $sensus = dataSensusPeserta::where('id', '=', $request->id)
            ->first();

        if (!empty($sensus)) {
            try {
                $sensus->update([
                    'id' => $request->id,
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
                    'user_id' => $request->user_id,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data sensus'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Sensus berhasil diupdate',
                'data_sensus' => $sensus,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Sensus tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $sensus = dataSensusPeserta::where('id', '=', $request->id)
            ->first();

        if (!empty($sensus)) {
            try {
                $sensus = dataSensusPeserta::where('id', '=', $request->id)
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
