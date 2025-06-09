<?php

namespace App\Http\Controllers;

use App\Models\dataSensusPeserta;
use App\Models\logs;
use App\Models\presensi;
use App\Models\presensiKegiatan;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\tblPekerjaan;
use Illuminate\Support\Str;

class DataSensusController extends Controller
{
    // public function list_nama_peserta()
    // {
    //     // Query untuk mendapatkan data peserta beserta nama kelompok
    //     $sensus = dataSensusPeserta::select([
    //         'tabel_kelompok.nama_kelompok as label',
    //         'data_peserta.nama_lengkap'
    //     ])
    //         ->join('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
    //         ->orderBy('tabel_kelompok.nama_kelompok')
    //         ->orderBy('data_peserta.nama_lengkap')
    //         ->get()
    //         ->groupBy('label'); // Grup berdasarkan nama kelompok

    //     // Transformasi data sesuai format yang diinginkan
    //     $formattedData = $sensus->map(function ($items, $label) {
    //         return [
    //             'label' => $label,
    //             'items' => $items->map(function ($item) {
    //                 return [
    //                     'nama_lengkap' => $item->nama_lengkap,
    //                 ];
    //             })->values()
    //         ];
    //     })->values();

    //     // Kembalikan response dalam format JSON
    //     return response()->json([
    //         'message' => 'Sukses',
    //         'data_sensus' => $formattedData,
    //         'success' => true,
    //     ], 200);
    // }

    protected $fonnteService;

    private function normalizeNama($nama)
    {
        // Normalisasi: hilangkan karakter non-huruf, ubah ke huruf kecil, transliterasi ASCII
        return strtolower(
            preg_replace('/[^a-z]/', '', Str::ascii($nama))
        );
    }

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    public function list_nama_peserta(Request $request)
    {
        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'exists' => ':attribute tidak ditemukan dalam sistem.',
        ]);

        // Cek role_id
        $operator = User::where('uuid', $request->id_operator)->first();

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin.',
            ], 403);
        }

        // Get data sensus
        $sensus = dataSensusPeserta::selectRaw('LOWER(nama_lengkap) AS nama_lengkap')
            ->distinct()
            ->orderByRaw('LOWER(nama_lengkap)')
            ->get()
            ->map(function ($item) {
                return [
                    'nama_lengkap' => ucwords($item->nama_lengkap),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Data nama peserta berhasil diambil.',
            'data' => $sensus,
        ], 200);
    }

    public function cari_data(Request $request)
    {
        $agent = new Agent();

        $id_operator = $request->query('id_operator');
        $nama_lengkap = $request->query('nama_lengkap');
        $tanggal_lahir = $request->query('tanggal_lahir');
        $nama_ortu = $request->query('nama_ortu');

        if (!$id_operator || !$nama_lengkap || !$tanggal_lahir || !$nama_ortu) {
            return response()->json([
                'success' => false,
                'message' => 'Semua parameter (nama_lengkap, tanggal_lahir, nama_ortu) wajib diisi.',
            ], 400);
        }

        $userOperator = User::where('uuid', $request->id_operator)->first();

        if (!$userOperator) {
            return response()->json([
                'success' => false,
                'message' => 'Petugas Operator tidak dikenali atau tidak ditemukan.',
            ], 404);
        }

        $sensus = dataSensusPeserta::select([
            'data_peserta.id',
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
            'tbl_pekerjaan.nama_pekerjaan AS pekerjaan',
            'data_peserta.usia_menikah',
            'data_peserta.kriteria_pasangan',
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
            'data_peserta.status_pernikahan',
            'data_peserta.status_sambung',
            'data_peserta.status_atlet_asad',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.jenis_data',
            'users.nama_lengkap AS user_petugas',
            'data_peserta.img',
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
        })->whereRaw('LOWER(data_peserta.nama_lengkap) LIKE ?', ['%' . strtolower($request->nama_lengkap) . '%'])
            ->whereRaw('LOWER(data_peserta.tanggal_lahir) = ?', [strtolower($request->tanggal_lahir)])
            ->where(function ($query) use ($request) {
                $query->whereRaw('LOWER(data_peserta.nama_ayah) LIKE ?', ['%' . strtolower($request->nama_ortu) . '%'])
                    ->orWhereRaw('LOWER(data_peserta.nama_ibu) LIKE ?', ['%' . strtolower($request->nama_ortu) . '%']);
            })
            ->first();

        // Tambahkan tanggal pencarian
        $tanggalPencarian = now()->format('Y-m-d H:i:s');

        // Lakukan panggilan HTTP untuk mendapatkan informasi IP
        // $response = Http::get('https://www.trackip.net/ip?json');

        // if (!$response->successful()) {
        //     return response()->json([
        //         'message' => 'Gagal mendapatkan IP pengguna. Status: ' . $response->status(),
        //         'success' => false,
        //     ], 500);
        // }

        // $ipFromResponse = $response->json()['IP'] ?? 'Unknown IP';

        try {
            if (!empty($sensus)) {
                $sensus->img_url = $sensus->img
                    ? asset('storage/' . str_replace('public/', '', $sensus->img))
                    : null;

                $logAccount = [
                    'user_id' => $userOperator->uuid,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Cari Data Sensus - [' . $sensus->id . ' - ' . $sensus->nama_lengkap . ' - ' . 'Web Data Center' . ']',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    // 'location_info' => $getDaata,
                    // 'latitude' => $getDaata['lat'],
                    // 'longitude' => $getDaata['lon'],
                    'engine_agent' => $request->header('user-agent'),
                ];
                logs::create($logAccount);

                return response()->json([
                    'success' => true,
                    'message' => 'Sukses',
                    'tanggal_pencarian' => $tanggalPencarian,
                    'data_sensus' => $sensus,
                    'digital' => $logAccount,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Sensus tidak ditemukan / tidak ada',
                ], 200);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Data Sensus tidak ditemukan' . $exception->getMessage(),
            ], 200);
        }
    }

    public function detail_sensus_personal(Request $request)
    {
        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
            'scan' => 'required',
        ]);

        // Cari operator berdasarkan UUID
        $operator = User::where('uuid', $request->id_operator)->first();

        // Cek hak akses operator
        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin.',
            ], 403);
        }

        // Ambil data dari tabel dataSensusPeserta (hanya satu tabel)
        $sensus = dataSensusPeserta::select([
            'kode_cari_data AS scan',
            'nama_lengkap',
            'tanggal_lahir',
            'alamat',
            DB::raw("CASE
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
            WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
            ELSE 'Tidak dalam rentang usia'
        END AS status_kelas"),
            'status_sambung',
        ])
            ->where('kode_cari_data', $request->scan)
            ->first();

        // Response jika data ditemukan
        if ($sensus) {
            return response()->json([
                'success' => true,
                'message' => 'Sukses',
                'data_sensus' => $sensus,
            ], 200);
        }

        // Response jika data tidak ditemukan
        return response()->json([
            'success' => false,
            'message' => 'Data sensus tidak ditemukan.',
        ], 404);
    }

    public function record_presensi_manual(Request $request)
    {
        $agent = new Agent();
        $user = User::where('uuid', $request->id_operator)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $userId = $user->id;

        $customMessages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'exists' => ':attribute tidak ditemukan dalam sistem.',
        ];

        $request->validate([
            // 'phone' => 'required|string',
            'id_operator' => 'required|exists:users,uuid',
            'kode_kegiatan' => 'required|string',
            'status_presensi' => 'required',
            'keterangan' => 'required',
            'nama_lengkap' => 'required',
        ], $customMessages);

        $userOperator = User::where('uuid', $request->id_operator)->first();
        $kegiatan = presensiKegiatan::where('kode_kegiatan', $request->kode_kegiatan)->first();

        if (!$userOperator) {
            return response()->json([
                'success' => false,
                'message' => 'Petugas Operator tidak dikenali atau tidak ditemukan.',
            ], 404);
        }

        if (!$kegiatan) {
            return response()->json([
                'message' => 'Kegiatan tidak ditemukan',
                'success' => false,
            ], 404);
        }

        $currentTime = now();
        $waktuKegiatan = Carbon::parse("{$kegiatan->tgl_kegiatan} {$kegiatan->jam_kegiatan}");

        if ($currentTime->lt($waktuKegiatan)) {
            return response()->json([
                'message' => 'Presensi belum bisa dilakukan. Tunggu hingga kegiatan dimulai pada '
                    . $kegiatan->tgl_kegiatan . ' jam ' . $kegiatan->jam_kegiatan,
                'success' => false,
            ], 403);
        }

        if ($currentTime->greaterThan($kegiatan->expired_date_time)) {
            return response()->json([
                'message' => 'Presensi sudah tidak bisa dilakukan. Waktu telah berakhir.',
                'success' => false,
            ], 403);
        }

        $peserta = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.nama_lengkap',
            'data_peserta.status_sambung',
            'data_peserta.status_pernikahan',
            DB::raw('TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) AS usia'),
        ])
            // Join tabel-tabel yang diperlukan
            ->where('data_peserta.nama_lengkap', $request->nama_lengkap);

        if ($kegiatan->tmpt_daerah || $kegiatan->tmpt_desa || $kegiatan->tmpt_kelompok) {
            $peserta->where(function ($query) use ($kegiatan) {
                if ($kegiatan->tmpt_daerah) {
                    $query->where('data_peserta.tmpt_daerah', $kegiatan->tmpt_daerah);
                }
                if ($kegiatan->tmpt_desa) {
                    $query->where('data_peserta.tmpt_desa', $kegiatan->tmpt_desa);
                }
                if ($kegiatan->tmpt_kelompok) {
                    $query->where('data_peserta.tmpt_kelompok', $kegiatan->tmpt_kelompok);
                }
            });
        }

        $peserta = $peserta->first();

        if (!$peserta) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan atau data tidak sesuai',
                'success' => false,
            ], 404);
        }

        if ($peserta->status_sambung != 1 || $peserta->status_pernikahan != 0) {
            return response()->json([
                'message' => 'Presensi ditolak. Peserta sudah pindah sambung atau menikah.',
                'success' => false,
            ], 403);
        }

        $usiaOperator = $kegiatan->usia_operator;
        $usiaBatas = $kegiatan->usia_batas;

        if (!empty($usiaOperator) && !empty($usiaBatas)) {
            $usia = $peserta->usia;

            if (!eval("return {$usia} {$usiaOperator} {$usiaBatas};")) {
                return response()->json([
                    'message' => 'Peserta tidak memenuhi kriteria usia.',
                    'success' => false,
                ], 403);
            }
        }

        $alreadyPresensi = presensi::where('id_kegiatan', $kegiatan->id)
            ->where('id_peserta', $peserta->id)
            ->exists();

        if ($alreadyPresensi) {
            return response()->json([
                'message' => 'Peserta sudah melakukan presensi sebelumnya.',
                'success' => false,
            ], 409);
        }

        $presensi = new presensi();
        $presensi->id_kegiatan = $kegiatan->id;
        $presensi->id_peserta = $peserta->id;
        $presensi->id_petugas = $kegiatan->add_by_petugas;
        $presensi->status_presensi = $request->status_presensi;
        $presensi->waktu_presensi = now();
        $presensi->keterangan = $request->keterangan;
        // $phone = $request->phone;
        // $countryCode = '62';

        // $templates = [
        //     'attendance' => "✅ *Absensi Berhasil Dicatat!*\n\n"
        //         . "📌 *Nama:* " . ($peserta->nama_lengkap ?? '*Peserta Tidak Diketahui*') . "\n"
        //         . "📅 *Tgl. Absen:* " . $presensi->waktu_presensi . "\n"
        //         . "📢 *Kegiatan:* " . ($kegiatan->nama_kegiatan ?? '*Tidak Diketahui*') . "\n"
        //         . "📍 *Tempat:* " . ($kegiatan->tmpt_kegiatan ?? '*Tidak Diketahui*') . "\n"
        //         . "🛠️ *Method:* Manual Via Data Center\n"
        //         . "🔍 *Status:* " . ($presensi->status_presensi ?? '*Tidak Diketahui*') . "\n"
        //         . "📝 *Keterangan:* *" . $presensi->keterangan . "*\n\n"
        //         . "🙏 Terima kasih telah melakukan absensi. *Semoga harimu menyenangkan!* 😊"
        // ];

        // Pilih template yang diinginkan (default ke 'attendance')
        // $message = $templates['attendance'];

        try {
            $presensi->save();

            // $Seender = $this->fonnteService->sendWhatsAppMessage($phone, $message, $countryCode);

            logs::create([
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => "Absensi Manual Mandiri - [{$peserta->id}] - [{$peserta->nama_lengkap}]",
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
                // 'updated_fields' => json_encode($Seender),
            ]);

            return response()->json([
                'message' => 'Presensi berhasil dicatat.',
                // 'data_wa' => $Seender,
                'data_presensi' => $peserta,
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data presensi: ' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function check_nama_lengkap(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string',
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
        ]);

        $inputNama = $request->input('nama_lengkap');
        $normalizedInput = $this->normalizeNama($inputNama);

        $dataSemuaPeserta = \App\Models\dataSensusPeserta::select('nama_lengkap')->get();

        $thresholdSimilarity = 90; // persen kemiripan minimal dianggap sama
        $namaMirip = [];

        foreach ($dataSemuaPeserta as $peserta) {
            $normalizedExisting = $this->normalizeNama($peserta->nama_lengkap);

            similar_text($normalizedInput, $normalizedExisting, $percent);

            if ($percent >= $thresholdSimilarity) {
                $namaMirip[] = $peserta->nama_lengkap . " ({$percent}%)";
            }
        }

        if (count($namaMirip) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nama lengkap mirip dengan yang sudah terdaftar: ' . implode(', ', $namaMirip),
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nama lengkap tersedia.',
        ], 200);
    }

    public function list_pekerjaan(Request $request)
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
            'exists' => ':attribute yang dipilih tidak valid',
        ];

        // Tangani permintaan kosong
        if (!$request->all()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dikirimkan.',
            ], 400);
        }

        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
        ], $customMessages);

        // Cek role_id
        $operator = User::where('uuid', $request->id_operator)->first();

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin.',
            ], 403);
        }

        $data_pekerjaan = tblPekerjaan::select(['id', 'nama_pekerjaan'])
            ->groupBy('id', 'nama_pekerjaan')->orderBy('nama_pekerjaan')->get();

        return response()->json([
            'message' => 'Sukses',
            'data' => $data_pekerjaan,
            'success' => true,
        ], 200);
    }

    public function create_data_sensus(Request $request)
    {
        $agent = new Agent();
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
        $sensus = User::find($request->user_id);
        $operator = User::where('uuid', $request->id_operator)->first();

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
            'id_operator' => 'required|exists:users,uuid',
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

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin.',
            ], 403);
        }

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

        // Cek kemiripan nama_lengkap
        $namaInput = strtolower(preg_replace('/[^a-z0-9]/', '', $request->nama_lengkap));
        $existing = \App\Models\dataSensusPeserta::get(['nama_lengkap'])->filter(function ($item) use ($namaInput) {
            $namaDB = strtolower(preg_replace('/[^a-z0-9]/', '', $item->nama_lengkap));
            similar_text($namaInput, $namaDB, $percent);
            return $percent >= 85; // Sesuaikan threshold
        });

        if ($existing->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Nama lengkap mirip dengan data yang sudah ada: ' . $existing->implode('nama_lengkap', ', '),
            ], 409);
        }

        DB::beginTransaction();

        try {

            $tabel_sensus->save();

            logs::create([
                'user_id' => $request->user_id,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Create Data Sensus By Data Center - [' . $tabel_sensus->id . '] - [' . $tabel_sensus->nama_lengkap . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ]);

            DB::commit();
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
}
