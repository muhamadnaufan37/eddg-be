<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\dataSensusPeserta;
use App\Models\tblCppdb;
use Jenssegers\Agent\Agent;
use App\Models\logs;
use App\Models\presensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PesertaDidikController extends Controller
{
    public function data_all_peserta_didik_aktif()
    {
        $user = auth()->user();
        $roleID = $user->role_id;
        $userID = $user->id;

        $table_peserta_didik = dataSensusPeserta::select(['id', 'nama_lengkap'])
            ->where('status_sambung', 1)
            ->where('status_pernikahan', 0)
            ->where('jenis_data', "KBM");

        if ($roleID != 1) {
            $table_peserta_didik = $table_peserta_didik->where('user_id', $userID);
        }

        $table_peserta_didik = $table_peserta_didik
            ->groupBy('id', 'nama_lengkap')
            ->orderBy('nama_lengkap')
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $statusPeserta = $request->get('status_sambung', null);
        $jenisKelamin = $request->get('jenis_kelamin', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.nama_lengkap',
            'data_peserta.status_sambung',
            'users.nama_lengkap AS nama_petugas',
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
            'tabel_daerah.nama_daerah AS nama_daerah',
            'tabel_desa.nama_desa AS nama_desa',
            'tabel_kelompok.nama_kelompok AS nama_kelompok',
            'data_peserta.created_at',
        ])
            ->leftJoin('users', 'data_peserta.user_id', '=', 'users.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->where('jenis_data', "KBM");

        if (!is_null($statusPeserta)) {
            $model->where('data_peserta.status_sambung', '=', $statusPeserta);
        }

        if (!is_null($jenisKelamin)) {
            $model->where('data_peserta.jenis_kelamin', '=', $jenisKelamin);
        }

        // Apply orderByRaw before executing the query
        $model->orderByRaw('data_peserta.created_at IS NULL, data_peserta.created_at DESC');

        if (!empty($keyword)) {
            $model->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
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
                    ->orWhere('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%');
            });
        }

        $table_peserta_didik = $model->paginate($perPage);
        $table_peserta_didik->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function listByKbm(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $statusPeserta = $request->get('status_sambung', null);
        $jenisKelamin = $request->get('jenis_kelamin', null);
        $dataDaerah = $request->get('role_daerah', $user->role_daerah);
        $dataDesa = $request->get('role_desa', $user->role_desa);
        $dataKelompok = $request->get('role_kelompok', $user->role_kelompok);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.nama_lengkap',
            'data_peserta.status_sambung',
            'users.nama_lengkap AS nama_petugas',
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
            'tabel_daerah.nama_daerah AS nama_daerah',
            'tabel_desa.nama_desa AS nama_desa',
            'tabel_kelompok.nama_kelompok AS nama_kelompok',
            'data_peserta.created_at',
        ])
            ->leftJoin('users', 'data_peserta.user_id', '=', 'users.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->where('jenis_data', "KBM");

        if (!is_null($statusPeserta)) {
            $model->where('data_peserta.status_sambung', '=', $statusPeserta);
        }

        if (!is_null($jenisKelamin)) {
            $model->where('data_peserta.jenis_kelamin', '=', $jenisKelamin);
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

        // Apply orderByRaw before executing the query
        $model->orderByRaw('data_peserta.created_at IS NULL, data_peserta.created_at DESC');

        if (!empty($keyword)) {
            $model->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
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
                    ->orWhere('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%');
            });
        }

        $table_peserta_didik = $model->paginate($perPage);
        $table_peserta_didik->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
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
            'kode_cari_data' => 'unique:data_peserta',
            'nama_lengkap' => 'required|max:225|unique:data_peserta',
            'nama_panggilan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'alamat' => 'required',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'nama_ayah' => 'required|string',
            'nama_ibu' => 'required|string',
            'hoby' => 'required|string|max:225',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'required|integer|digits_between:1,5',
            'tmpt_kelompok' => 'required|integer|digits_between:1,5',
            'img' => 'nullable|image|mimes:jpg,png|max:5120',
            'status_atlet_asad' => 'required|integer',
        ], $customMessages);

        $table_peserta_didik = new dataSensusPeserta();
        $tanggalSekarang = Carbon::now();
        $prefix = 'KBM';

        do {
            $kodeUnik = $prefix . $tanggalSekarang->format('ymdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        } while (\App\Models\dataSensusPeserta::where('kode_cari_data', $kodeUnik)->exists());

        $table_peserta_didik->kode_cari_data = $kodeUnik;
        $table_peserta_didik->nama_lengkap = $request->nama_lengkap;
        $table_peserta_didik->nama_panggilan = ucwords(strtolower($request->nama_panggilan));
        $table_peserta_didik->tempat_lahir = ucwords(strtolower($request->tempat_lahir));
        $table_peserta_didik->tanggal_lahir = $request->tanggal_lahir;
        $table_peserta_didik->alamat = ucwords(strtolower($request->alamat));
        $table_peserta_didik->jenis_kelamin = $request->jenis_kelamin;
        $table_peserta_didik->no_telepon = "00000000000";
        $table_peserta_didik->nama_ayah = $request->nama_ayah;
        $table_peserta_didik->nama_ibu = $request->nama_ibu;
        $table_peserta_didik->hoby = $request->hoby;
        $table_peserta_didik->pekerjaan = 1;
        $table_peserta_didik->tmpt_daerah = $request->tmpt_daerah;
        $table_peserta_didik->tmpt_desa = $request->tmpt_desa;
        $table_peserta_didik->tmpt_kelompok = $request->tmpt_kelompok;
        $table_peserta_didik->status_sambung = 1;
        $table_peserta_didik->status_pernikahan = 0;
        $table_peserta_didik->jenis_data = "KBM";
        $table_peserta_didik->img = $request->img;
        $table_peserta_didik->status_atlet_asad = $request->status_atlet_asad;
        $table_peserta_didik->user_id = $userId;

        // Menyimpan gambar jika diunggah
        if ($request->hasFile('img')) {
            $foto = $request->file('img'); // Get the uploaded file

            // Generate a unique filename
            $namaFile = Str::slug($table_peserta_didik->nama_lengkap) . '.' . $foto->getClientOriginalExtension();

            // Save the file to the 'public/images/sensus' directory
            $path = $foto->storeAs('public/images/sensus', $namaFile);

            // Update the database record
            $table_peserta_didik->img = $path;
        } else {
            $table_peserta_didik->img = null; // Handle cases where no file is uploaded
        }
        try {
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

            if (!$userId) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            $table_peserta_didik->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Create Data Peserta Didik - [' . $table_peserta_didik->id . '] - [' . $table_peserta_didik->nama_lengkap . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ];
            logs::create($logAccount);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data Peserta Didik' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_peserta_didik->created_at, $table_peserta_didik->updated_at);

        return response()->json([
            'message' => 'Data Peserta Didik berhasil ditambahkan',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_peserta_didik = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.kode_cari_data',
            'data_peserta.nama_lengkap',
            'data_peserta.nama_panggilan',
            'data_peserta.tempat_lahir',
            'data_peserta.tanggal_lahir',
            'data_peserta.alamat',
            DB::raw('TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) AS umur'),
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
            'data_peserta.jenis_kelamin',
            'data_peserta.no_telepon',
            'data_peserta.nama_ayah',
            'data_peserta.nama_ibu',
            'data_peserta.hoby',
            'data_peserta.status_sambung',
            'data_peserta.status_atlet_asad',
            'data_peserta.user_id',
            'data_peserta.tmpt_daerah',
            'data_peserta.tmpt_desa',
            'data_peserta.tmpt_kelompok',
            'users.nama_lengkap AS nama_petugas',
            'data_peserta.img',
            'data_peserta.created_at AS tanggal_input',
        ])
            ->leftJoin('users', 'data_peserta.user_id', '=', 'users.id')
            ->leftJoin('tabel_daerah', 'data_peserta.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'data_peserta.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->where('data_peserta.id', $request->id)
            ->first();

        if ($table_peserta_didik) {
            // Generate the correct URL for the image
            $table_peserta_didik->img_url = $table_peserta_didik->img
                ? asset('storage/' . str_replace('public/', '', $table_peserta_didik->img))
                : null;

            unset($table_peserta_didik->created_at, $table_peserta_didik->updated_at);

            return response()->json([
                'message' => 'Data Peserta Ditemukan',
                'data_peserta_didik' => $table_peserta_didik,
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
        $userId = Auth::id();
        $agent = new Agent();
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);

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
            'nama_lengkap' => 'sometimes|required|max:225|string|unique:data_peserta,nama_lengkap,' . $request->id . ',id',
            'nama_panggilan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'alamat' => 'required',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'nama_ayah' => 'required|string|max:225',
            'nama_ibu' => 'required|string|max:225',
            'hoby' => 'required|string|max:225',
            'status_sambung' => 'required',
            'status_atlet_asad' => 'required',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'required|integer|digits_between:1,5',
            'tmpt_kelompok' => 'required|integer|digits_between:1,5',
        ], $customMessages);

        $table_peserta_didik = dataSensusPeserta::where('id', '=', $request->id)
            ->first();

        if (!$table_peserta_didik) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
                'success' => false,
            ], 404);
        }

        try {
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

            $originalData = $table_peserta_didik->getOriginal();

            $table_peserta_didik->fill([
                'nama_lengkap' => $request->nama_lengkap,
                'nama_panggilan' => ucwords(strtolower($request->nama_panggilan)),
                'tempat_lahir' => ucwords(strtolower($request->tempat_lahir)),
                'tanggal_lahir' => $request->tanggal_lahir,
                'alamat' => ucwords(strtolower($request->alamat)),
                'jenis_kelamin' => $request->jenis_kelamin,
                'nama_ayah' => $request->nama_ayah,
                'nama_ibu' => $request->nama_ibu,
                'hoby' => $request->hoby,
                'status_sambung' => $request->status_sambung,
                'status_atlet_asad' => $request->status_atlet_asad,
                'tmpt_daerah' => $request->tmpt_daerah,
                'tmpt_desa' => $request->tmpt_desa,
                'tmpt_kelompok' => $request->tmpt_kelompok,
                'img' => $request->img,
            ]);

            if ($request->hasFile('img')) {
                $request->validate([
                    'img' => 'nullable|image|mimes:jpg,png|max:5120',
                ], $customMessages);
                $oldImgPath = storage_path('public/images/sensus/' . $table_peserta_didik->img);

                // Hapus file lama jika ada
                if (file_exists($oldImgPath) && $table_peserta_didik->img) {
                    unlink($oldImgPath);
                }

                // Save the file to the 'public/images/sensus' directory
                $newImg = $request->file('img');
                $namaFile = Str::slug($table_peserta_didik->nama_lengkap) . '.' . $newImg->getClientOriginalExtension();
                $path = $newImg->storeAs('public/images/sensus', $namaFile);
                $table_peserta_didik->img = $path;
            }

            $updatedFields = [];
            foreach ($table_peserta_didik->getDirty() as $field => $newValue) {
                $oldValue = $originalData[$field] ?? null; // Ambil nilai lama
                $updatedFields[] = "$field: [$oldValue] -> [$newValue]";
            }

            // Simpan perubahan ke database
            $table_peserta_didik->save();

            // Log perubahan
            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Update Data Peserta Didik - [' . $table_peserta_didik->id . '] - [' . $table_peserta_didik->nama_lengkap . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
                'updated_fields' => json_encode($updatedFields), // Simpan sebagai JSON
            ];
            logs::create($logAccount);

            return response()->json([
                'message' => 'Data Peserta Didik berhasil diupdate',
                'data_peserta_didik' => $table_peserta_didik,
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal mengupdate Data: ' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();

        // Validasi input
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_peserta_didik = dataSensusPeserta::where('id', $request->id)->first();

        if ($table_peserta_didik) {
            $existsInCppdb = tblCppdb::where('id_peserta', $request->id)->exists();
            $existsInPresensi = presensi::where('id_peserta', $request->id)->exists();

            if ($existsInCppdb && $existsInPresensi) {
                return response()->json([
                    'message' => 'Data sensus tidak dapat dihapus karena sudah terdaftar dan digunakan di tabel lain',
                    'success' => false,
                ], 409);
            }

            try {
                if (!empty($table_peserta_didik->img)) {
                    $filePath = storage_path('app/' . $table_peserta_didik->img);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $deletedData = $table_peserta_didik->toArray();

                $table_peserta_didik->delete();

                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Delete Data Peserta Didik - [' . $deletedData['id'] . '] - [' . $deletedData['nama_lengkap'] . ']',
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
                return response()->json([
                    'message' => 'Gagal menghapus Data: ' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 404);
    }
}
