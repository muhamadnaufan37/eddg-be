<?php

namespace App\Http\Controllers;

use App\Models\dataSensusPeserta;
use App\Models\logs;
use App\Models\presensi;
use App\Models\presensiKegiatan;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;

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
        $response = Http::get('https://www.trackip.net/ip?json');

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Gagal mendapatkan IP pengguna. Status: ' . $response->status(),
                'success' => false,
            ], 500);
        }

        $ipFromResponse = $response->json()['IP'] ?? 'Unknown IP';

        try {
            if (!empty($sensus)) {
                $sensus->img_url = $sensus->img
                    ? asset('storage/' . str_replace('public/', '', $sensus->img))
                    : null;

                $logAccount = [
                    'user_id' => $userOperator->uuid,
                    'ip_address' => $ipFromResponse,
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
            'phone' => 'required|string',
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
        $phone = $request->phone;
        $countryCode = '62';

        $templates = [
            'attendance' => "✅ *Absensi Berhasil Dicatat!*\n\n"
                . "📌 *Nama:* " . ($peserta->nama_lengkap ?? '*Peserta Tidak Diketahui*') . "\n"
                . "📅 *Tgl. Absen:* " . $presensi->waktu_presensi . "\n"
                . "📢 *Kegiatan:* " . ($kegiatan->nama_kegiatan ?? '*Tidak Diketahui*') . "\n"
                . "📍 *Tempat:* " . ($kegiatan->tmpt_kegiatan ?? '*Tidak Diketahui*') . "\n"
                . "🛠️ *Method:* Manual Via Data Center\n"
                . "🔍 *Status:* " . ($presensi->status_presensi ?? '*Tidak Diketahui*') . "\n"
                . "📝 *Keterangan:* *" . $presensi->keterangan . "*\n\n"
                . "🙏 Terima kasih telah melakukan absensi. *Semoga harimu menyenangkan!* 😊"
        ];

        // Pilih template yang diinginkan (default ke 'attendance')
        $message = $templates['attendance'];

        try {
            $presensi->save();

            $Seender = $this->fonnteService->sendWhatsAppMessage($phone, $message, $countryCode);

            logs::create([
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => "Absensi Manual Mandiri - [{$peserta->id}] - [{$peserta->nama_lengkap}]",
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
                'updated_fields' => json_encode($Seender),
            ]);

            return response()->json([
                'message' => 'Presensi berhasil dicatat.',
                'data_wa' => $Seender,
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
}
