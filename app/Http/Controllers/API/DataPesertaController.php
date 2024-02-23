<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\dataSensusPeserta;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataPesertaController extends Controller
{

    public function dashboard_sensus(Request $request)
    {
        $user = $request->user();
        $dataDaerah = $request->get('data-daerah', $user->role_daerah);
        $dataDesa = $request->get('data-desa', $user->role_desa);
        $dataKelompok = $request->get('data-kelompok', $user->role_kelompok);

        $query = dataSensusPeserta::select([
            DB::raw("COALESCE(EXTRACT(MONTH FROM data_peserta.created_at), 1) AS month"),
            DB::raw('COUNT(*) AS total_data'),
            DB::raw("
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 13 THEN 1
                    ELSE 0
                END) AS total_pra_remaja"),
            DB::raw("
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 13
                    AND EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) <= 16 THEN 1
                    ELSE 0
                END) AS total_remaja"),
            DB::raw("
                SUM(CASE
                    WHEN EXTRACT(YEAR FROM AGE(current_date, data_peserta.tanggal_lahir)) > 16 THEN 1
                    ELSE 0
                END) AS total_muda_mudi_usia_nikah"),
            DB::raw('SUM(CASE WHEN data_peserta.jenis_kelamin = \'LAKI-LAKI\' THEN 1 ELSE 0 END) AS total_laki'),
            DB::raw('SUM(CASE WHEN data_peserta.jenis_kelamin = \'PEREMPUAN\' THEN 1 ELSE 0 END) AS total_perempuan'),
        ])
            ->leftJoin('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS BIGINT)'))
            ->leftJoin('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS BIGINT)'))
            ->leftJoin('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS BIGINT)'))
            ->leftJoin('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS BIGINT)'))
            ->groupBy('month')
            ->orderBy('month');

        if (!is_null($dataDaerah)) {
            $query->where('tabel_daerah.id', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $query->where('tabel_desa.id', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $query->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        // Inisialisasi array untuk menyimpan data bulan
        $dataBulan = [];
        for ($i = 1; $i <= 12; $i++) {
            $dataBulan[$i] = [
                'month' => $i,
                'total_data' => 0,
                'total_pra_remaja' => 0,
                'total_remaja' => 0,
                'total_muda_mudi_usia_nikah' => 0,
                'total_laki' => 0,
                'total_perempuan' => 0,
            ];
        }

        $results = $query->get();

        $data_sensus_thl = [];

        foreach ($results as $result) {
            $data_sensus_thl[] = [
                'bulan' => str_pad($result->month, 2, '0', STR_PAD_LEFT),
                'total_data' => $result->total_data,
                'total_pra_remaja' => $result->total_pra_remaja,
                'total_remaja' => $result->total_remaja,
                'total_muda_mudi_usia_nikah' => $result->total_muda_mudi_usia_nikah,
                'total_laki' => $result->total_laki,
                'total_perempuan' => $result->total_perempuan,
            ];
        }

        return response()->json([
            'message' => 'Success',
            'data_sensus_thl' => $data_sensus_thl,
            'success' => true
        ], 200);

        // Lakukan apa pun yang perlu Anda lakukan dengan $dataBulan

    }

    public function list_daerah()
    {
        $sensus = dataDaerah::select(['nama_daerah'])
            ->groupBy('nama_daerah')->orderBy('nama_daerah')->get();

        return response()->json([
            'message'       => 'Sukses',
            'data_tempat_sambung'    => $sensus,
            'success'       => true
        ], 200);
    }

    public function list_desa()
    {
        $sensus = dataDesa::select(['nama_desa'])
            ->groupBy('nama_desa')->orderBy('nama_desa')->get();

        return response()->json([
            'message'       => 'Sukses',
            'data_tempat_sambung'    => $sensus,
            'success'       => true
        ], 200);
    }

    public function list_kelompok()
    {
        $sensus = dataKelompok::select(['nama_kelompok'])
            ->groupBy('nama_kelompok')->orderBy('nama_kelompok')->get();

        return response()->json([
            'message'       => 'Sukses',
            'data_tempat_sambung'    => $sensus,
            'success'       => true
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $dataDaerah = $request->get('data-daerah', null);
        $dataDesa = $request->get('data-desa', null);
        $dataKelompok = $request->get('data-kelompok', null);

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
                ELSE 'Muda-mudi / Usia Nikah'
            END AS status_kelas")
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS BIGINT)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS BIGINT)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS BIGINT)'))
            ->join('users', 'users.id', '=', DB::raw('CAST(data_peserta.user_id AS BIGINT)'));

        if (!is_null($dataDaerah)) {
            $query->where('tabel_daerah.nama_daerah', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $query->where('tabel_desa.nama_desa', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $query->where('tabel_kelompok.nama_kelompok', '=', $dataKelompok);
        }

        // Apply orderByRaw before executing the query
        $query->orderByRaw('data_peserta.created_at DESC NULLS LAST');

        $query->where('data_peserta.status_pernikahan', '!=', true)
            ->where('data_peserta.status_sambung', '!=', 0);

        if (!empty($keyword) && empty($kolom)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.kode_cari_data', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.nama_panggilan', 'ILIKE', '%' . $keyword . '%');
            });
        } else if (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_cari_data') {
                $kolom = 'data_peserta.kode_cari_data';
            } else {
                $kolom = 'data_peserta.kode_cari_data';
            }

            $query->where($kolom, 'ILIKE', '%' . $keyword . '%');
        }

        $sensus = $query->paginate($perPage);

        $sensus->appends([
            'per-page' => $perPage,
        ]);

        return response()->json([
            'message'   => 'Sukses',
            'data_sensus' => $sensus,
            'success'   => true
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
                ELSE 'Muda-mudi / Usia Nikah'
            END AS status_kelas")
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
                $q->where('data_peserta.nama_lengkap', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.kode_cari_data', 'ILIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.nama_panggilan', 'ILIKE', '%' . $keyword . '%');
            });
        } else if (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_cari_data') {
                $kolom = 'data_peserta.kode_cari_data';
            } else {
                $kolom = 'data_peserta.kode_cari_data';
            }

            $query->where($kolom, 'ILIKE', '%' . $keyword . '%');
        }

        $sensus = $query->paginate($perPage);

        $sensus->appends([
            'per-page' => $perPage,
        ]);

        return response()->json([
            'message'   => 'Sukses',
            'data_sensus' => $sensus,
            'success'   => true
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
            'pekerjaan' => 'required|string',
            'usia_menikah' => 'nullable|string',
            'kriteria_pasangan' => 'nullable|string',
            'tmpt_daerah' => 'integer|digits_between:1,1',
            'tmpt_desa' => 'integer|digits_between:1,1',
            'tmpt_kelompok' => 'integer|digits_between:1,1',
            'user_id' => 'required|integer',
        ], $customMessages);

        $tabel_sensus = new dataSensusPeserta;

        $tanggalSekarang = Carbon::now();
        $bulan = $tanggalSekarang->format('m'); // Mendapatkan bulan saat ini (format 2 digit)
        $tahun = $tanggalSekarang->format('Y'); // Mendapatkan tahun saat ini (format 4 digit)

        $tabel_sensus->kode_cari_data = $bulan . Str::random(4) . $tahun;
        $tabel_sensus->nama_lengkap = $request->nama_lengkap;
        $tabel_sensus->nama_panggilan = $request->nama_panggilan;
        $tabel_sensus->tempat_lahir = $request->tempat_lahir;
        $tabel_sensus->tanggal_lahir = $request->tanggal_lahir;
        $tabel_sensus->alamat = $request->alamat;
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

        try {
            $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
            $tabel_desa = dataDesa::find($request->tmpt_desa);
            $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
            $sensuss = User::find($request->user_id);

            if (!$tabel_daerah) {
                return response()->json([
                    'message' => 'Daerah tidak ditemukan',
                    'success' => false
                ], 404);
            }

            if (!$tabel_desa) {
                return response()->json([
                    'message' => 'Desa tidak ditemukan',
                    'success' => false
                ], 404);
            }

            if (!$tabel_kelompok) {
                return response()->json([
                    'message' => 'Kelompok tidak ditemukan',
                    'success' => false
                ], 404);
            }

            if (!$sensuss) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false
                ], 404);
            }

            $tabel_sensus->save();
        } catch (Exception $exception) {
            return response()->json([
                'message'   => 'Gagal menambah data sensus' . $exception->getMessage(),
                'success'   => false
            ], 500);
        }

        unset($tabel_sensus->created_at, $tabel_sensus->updated_at);

        return response()->json([
            'message'   => 'Data sensus berhasil ditambahkan',
            'data_sensus' => $tabel_sensus,
            'success'   => true
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5'
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
            ELSE 'Muda-mudi / Usia Nikah'
        END AS status_kelas
        ")
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
            return response()->json([
                'message'   => 'Sukses',
                'data_sensus' => $sensus,
                'success'   => true
            ], 200);
        }

        return response()->json([
            'message'   => 'Data Sensus tidak ditemukan',
            'success'   => false
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
            'nama_lengkap' => 'sometimes|required|string|unique:data_peserta,nama_lengkap,' . $request->id . ',id',
            'nama_panggilan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'alamat' => 'required|string',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'no_telepon' => 'sometimes|required|string|digits_between:8,13|unique:data_peserta,no_telepon,' . $request->id . ',id',
            'nama_ayah' => 'required|string',
            'nama_ibu' => 'required|string',
            'hoby' => 'required|string',
            'pekerjaan' => 'required|string',
            'usia_menikah' => 'nullable|string',
            'kriteria_pasangan' => 'nullable|string',
            'status_sambung' => 'integer',
            'status_pernikahan' => 'boolean',
            'user_id' => 'required|integer'
        ], $customMessages);

        $sensus = dataSensusPeserta::where('id', '=', $request->id)
            ->first();

        if (!empty($sensus)) {
            try {
                $sensus->update([
                    'id' => $request->id,
                    'nama_lengkap' => $request->nama_lengkap,
                    'nama_panggilan' => $request->nama_panggilan,
                    'tempat_lahir' => $request->tempat_lahir,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'alamat' => $request->alamat,
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
                    'user_id' => $request->user_id
                ]);
            } catch (Exception $exception) {
                return response()->json([
                    'message'   => 'Gagal mengupdate data sensus' . $exception->getMessage(),
                    'success'   => false
                ], 500);
            }

            return response()->json([
                'message'   => 'Data Sensus berhasil diupdate',
                'data_sensus' => $sensus,
                'success'   => true
            ], 200);
        }

        return response()->json([
            'message'   => 'Data Sensus tidak ditemukan',
            'success'   => false
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5'
        ]);

        $sensus = dataSensusPeserta::where('id', '=', $request->id)
            ->first();

        if (!empty($sensus)) {
            try {
                $sensus = dataSensusPeserta::where('id', '=', $request->id)
                    ->delete();
                return response()->json([
                    'message'   => 'Data Sensus berhasil dihapus',
                    'success'   => true
                ], 200);
            } catch (Exception $exception) {
                return response()->json([
                    'message'   => 'Gagal menghapus data sensus' . $exception->getMessage(),
                    'success'   => false
                ], 500);
            }
        }

        return response()->json([
            'message'   => 'Data Sensus tidak ditemukan',
            'success'   => false
        ], 200);
    }
}
