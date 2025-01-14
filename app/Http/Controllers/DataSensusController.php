<?php

namespace App\Http\Controllers;

use App\Models\dataSensusPeserta;
use App\Models\logs;
use App\Models\presensi;
use App\Models\presensiKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;
use Carbon\Carbon;

class DataSensusController extends Controller
{

    public function list_nama_peserta()
    {
        // Query untuk mendapatkan data peserta beserta nama kelompok
        $sensus = dataSensusPeserta::select([
            'tabel_kelompok.nama_kelompok as label',
            'data_peserta.nama_lengkap'
        ])
            ->join('tabel_kelompok', 'data_peserta.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->orderBy('tabel_kelompok.nama_kelompok')
            ->orderBy('data_peserta.nama_lengkap')
            ->get()
            ->groupBy('label'); // Grup berdasarkan nama kelompok

        // Transformasi data sesuai format yang diinginkan
        $formattedData = $sensus->map(function ($items, $label) {
            return [
                'label' => $label,
                'items' => $items->map(function ($item) {
                    return [
                        'nama_lengkap' => $item->nama_lengkap,
                    ];
                })->values()
            ];
        })->values();

        // Kembalikan response dalam format JSON
        return response()->json([
            'message' => 'Sukses',
            'data_sensus' => $formattedData,
            'success' => true,
        ], 200);
    }

    public function cari_data(Request $request)
    {
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
            'nama_lengkap' => 'required',
            'tanggal_lahir' => 'required',
            'nama_ortu' => 'required',
        ], $customMessages);

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

        // Periksa apakah panggilan berhasil
        if ($response->successful()) {
            // Ambil data JSON dari respons
            $ipInfo = $response->json();

            // Ambil IP dari respons JSON
            $ipFromResponse = $ipInfo['IP'];

            // $getDaata = Http::get("http://ip-api.com/json/{$ipFromResponse}")->json();

            // Buat log dengan informasi IP dari respons
            $logAccount = [
                'user_id' => 0,
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
        } else {
            return response()->json([
                'message' => 'Data Sensus tidak ditemukan' . $response->status(),
                'success' => false,
            ], 200);
        }

        try {
            if (!empty($sensus)) {
                if ($sensus->img_sensus) {
                    $sensus->image_url = asset($sensus->img_sensus);
                } else {
                    $sensus->image_url = '';
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Data Berhasil Ditemukan',
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

    public function record_presensi_manual(Request $request)
    {
        $agent = new Agent();

        $request->validate([
            'kode_kegiatan' => 'required|string',
            'status_presensi' => 'required',
            'keterangan' => 'required',
            'nama_lengkap' => 'required',
            'tanggal_lahir' => 'required',
            'nama_ortu' => 'required',
        ]);

        $kegiatan = presensiKegiatan::where('kode_kegiatan', $request->kode_kegiatan)->first();

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
            ->where('data_peserta.nama_lengkap', $request->nama_lengkap)
            ->where('data_peserta.tanggal_lahir', $request->tanggal_lahir)
            ->where(function ($query) use ($request) {
                $query->where('data_peserta.nama_ayah', $request->nama_ortu)
                    ->orWhere('data_peserta.nama_ibu', $request->nama_ortu);
            });

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

        try {
            $presensi->save();

            logs::create([
                'user_id' => 0,
                'ip_address' => $request->ip(),
                'aktifitas' => "Absensi Manual Mandiri - [{$peserta->id}] - [{$peserta->nama_lengkap}]",
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ]);

            return response()->json([
                'message' => 'Presensi berhasil dicatat.',
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
