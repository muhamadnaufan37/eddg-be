<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataSensusPeserta;
use App\Models\presensi;
use App\Models\presensiKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\logs;
use App\Services\FonnteService;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
{
    protected $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    public function list_nama_peserta()
    {
        // Get data sensus
        $sensus = dataSensusPeserta::select('kode_cari_data', 'nama_lengkap')
            ->distinct()
            ->orderByRaw('LOWER(nama_lengkap) ASC')
            ->get()
            ->map(function ($item) {
                return [
                    'kode_cari_data' => $item->kode_cari_data,
                    'nama_lengkap' => ucwords(strtolower($item->nama_lengkap)),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Data nama peserta berhasil diambil.',
            'data' => $sensus,
        ], 200);
    }

    public function getPresensiReport(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        $dataDaerah   = $request->get('data-daerah', $user->role_daerah);
        $dataDesa     = $request->get('data-desa', $user->role_desa);
        $dataKelompok = $request->get('data-kelompok', $user->role_kelompok);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $request->validate([
            'id_kegiatan' => 'required|string',
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
        ]);

        $id_kegiatan = $request->id_kegiatan;

        // Cari kegiatan
        $kegiatan = PresensiKegiatan::where('id', $id_kegiatan)->first();

        if (!$kegiatan) {
            return response()->json([
                'message' => 'Kegiatan tidak ditemukan',
                'success' => false,
            ], 404);
        }

        // Query dasar peserta
        $pesertaQuery = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.nama_lengkap',
            'data_peserta.tanggal_lahir',
            'data_peserta.jenis_kelamin',
            'data_peserta.status_sambung',
            'data_peserta.status_pernikahan',
            'presensi.id AS presensi_id',
            'presensi.status_presensi',
            'presensi.keterangan',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'presensi.waktu_presensi',
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'))
            ->leftJoin('presensi', function ($join) use ($id_kegiatan) {
                $join->on('data_peserta.id', '=', 'presensi.id_peserta')
                    ->where('presensi.id_kegiatan', '=', $id_kegiatan);
            })
            ->when($kegiatan->tmpt_daerah, fn($q) => $q->where('data_peserta.tmpt_daerah', $kegiatan->tmpt_daerah))
            ->when($kegiatan->tmpt_desa, fn($q) => $q->where('data_peserta.tmpt_desa', $kegiatan->tmpt_desa))
            ->when($kegiatan->tmpt_kelompok, fn($q) => $q->where('data_peserta.tmpt_kelompok', $kegiatan->tmpt_kelompok))
            ->where('data_peserta.status_sambung', 1)
            ->where('data_peserta.status_pernikahan', 0)
            ->when($dataDaerah, fn($q) => $q->where('tabel_daerah.id', $dataDaerah))
            ->when($dataDesa, fn($q) => $q->where('tabel_desa.id', $dataDesa))
            ->when($dataKelompok, fn($q) => $q->where('tabel_kelompok.id', $dataKelompok))
            ->when(
                $kegiatan->usia_operator && $kegiatan->usia_batas,
                fn($q) => $q->whereRaw("TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) {$kegiatan->usia_operator} {$kegiatan->usia_batas}")
            )
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($query) use ($keyword) {
                    $query->where('data_peserta.nama_lengkap', 'LIKE', "%{$keyword}%")
                        ->orWhere('data_peserta.jenis_kelamin', 'LIKE', "%{$keyword}%")
                        ->orWhere('presensi.status_presensi', 'LIKE', "%{$keyword}%")
                        ->orWhere('tabel_daerah.nama_daerah', 'LIKE', "%{$keyword}%")
                        ->orWhere('tabel_desa.nama_desa', 'LIKE', "%{$keyword}%")
                        ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', "%{$keyword}%");
                });
            });
        // ->orderBy('data_peserta.nama_lengkap');

        // Hitung statistik langsung dari database
        $statistics = dataSensusPeserta::leftJoin('presensi', function ($join) use ($id_kegiatan) {
            $join->on('data_peserta.id', '=', 'presensi.id_peserta')
                ->where('presensi.id_kegiatan', '=', $id_kegiatan);
        })
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'))
            ->when($kegiatan->tmpt_daerah, fn($q) => $q->where('data_peserta.tmpt_daerah', $kegiatan->tmpt_daerah))
            ->when($kegiatan->tmpt_desa, fn($q) => $q->where('data_peserta.tmpt_desa', $kegiatan->tmpt_desa))
            ->when($kegiatan->tmpt_kelompok, fn($q) => $q->where('data_peserta.tmpt_kelompok', $kegiatan->tmpt_kelompok))
            ->where('data_peserta.status_sambung', 1)
            ->where('data_peserta.status_pernikahan', 0)
            ->when($dataDaerah, fn($q) => $q->where('tabel_daerah.id', $dataDaerah))
            ->when($dataDesa, fn($q) => $q->where('tabel_desa.id', $dataDesa))
            ->when($dataKelompok, fn($q) => $q->where('tabel_kelompok.id', $dataKelompok))
            ->when(
                $kegiatan->usia_operator && $kegiatan->usia_batas,
                fn($q) => $q->whereRaw("TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) {$kegiatan->usia_operator} {$kegiatan->usia_batas}")
            )
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($query) use ($keyword) {
                    $query->where('tabel_daerah.nama_daerah', 'LIKE', "%{$keyword}%")
                        ->orWhere('tabel_desa.nama_desa', 'LIKE', "%{$keyword}%")
                        ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', "%{$keyword}%");
                });
            })
            ->selectRaw('
            COUNT(CASE WHEN presensi.status_presensi = "HADIR" THEN 1 END) AS hadir,
            COUNT(CASE WHEN presensi.status_presensi = "TELAT HADIR" THEN 1 END) AS telat_hadir,
            COUNT(CASE WHEN presensi.status_presensi = "IZIN" THEN 1 END) AS izin,
            COUNT(CASE WHEN presensi.status_presensi = "SAKIT" THEN 1 END) AS sakit,
            COUNT(CASE WHEN presensi.status_presensi IS NULL THEN 1 ELSE NULL END) AS alfa
        ')
            ->first();

        // Transformasi data
        $allData = $pesertaQuery->get();
        $transformedData = $allData->map(fn($peserta) => [
            'id_peserta' => $peserta->id,
            'nama_lengkap' => $peserta->nama_lengkap,
            'tanggal_lahir' => $peserta->tanggal_lahir,
            'jenis_kelamin' => $peserta->jenis_kelamin,
            'status_presensi' => $peserta->presensi_id ? $peserta->status_presensi : 'alfa/tidak hadir',
            'status_sambung' => $peserta->status_sambung,
            'status_pernikahan' => $peserta->status_pernikahan,
            'nama_daerah' => $peserta->nama_daerah,
            'nama_desa' => $peserta->nama_desa,
            'nama_kelompok' => $peserta->nama_kelompok,
            'keterangan' => $peserta->keterangan,
            'waktu_presensi' => $peserta->waktu_presensi,
        ]);

        // Paginate data
        $reportData = $pesertaQuery->paginate($perPage);

        $reportData->appends(['per-page' => $perPage]);

        // Respon API
        return response()->json([
            'message' => 'Data presensi berhasil diambil',
            'list_data_presensi_peserta' => $reportData,
            'data_presensi_peserta' => $transformedData,
            'statistics' => $statistics,
            'success' => true,
        ], 200);
    }


    public function record_presensi_qrcode(Request $request)
    {
        $agent = new Agent();
        $userId = Auth::id();

        $request->validate([
            // 'phone' => 'required|string',
            'kode_cari_data' => 'required|string',
            'id_kegiatan' => 'required',
            'id_petugas' => 'required',
        ]);

        $kegiatan = presensiKegiatan::find($request->id_kegiatan);
        if (!$kegiatan) {
            return response()->json([
                'message' => 'Kegiatan tidak ditemukan',
                'success' => false,
            ], 404);
        }

        $currentTime = Carbon::now();
        if (empty($kegiatan->tgl_kegiatan) || empty($kegiatan->jam_kegiatan)) {
            return response()->json([
                'message' => 'Tanggal atau jam kegiatan tidak valid.',
                'success' => false,
            ], 400);
        }

        try {
            // Parsing waktu kegiatan
            $waktuKegiatan = Carbon::parse("{$kegiatan->tgl_kegiatan} {$kegiatan->jam_kegiatan}");
            $waktuMulaiPresensi = $waktuKegiatan->copy()->subMinutes(90);

            // Periksa apakah waktu presensi belum dimulai
            if ($currentTime->lt($waktuMulaiPresensi)) {
                return response()->json([
                    'message' => 'Presensi belum bisa dilakukan. Tunggu hingga waktu kegiatan dimulai pada ' . $waktuKegiatan->format('d-m-Y H:i') . ' waktu setempat.',
                    'success' => false,
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses waktu kegiatan.',
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        }

        if (now()->greaterThan($kegiatan->expired_date_time)) {
            return response()->json([
                'message' => 'Presensi sudah tidak bisa dilakukan, waktu telah berakhir',
                'success' => false,
            ], 403);
        }

        $peserta = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.kode_cari_data',
            'data_peserta.nama_lengkap',
            DB::raw('TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) AS usia'),
            'data_peserta.jenis_kelamin',
            'tbl_pekerjaan.nama_pekerjaan AS pekerjaan',
            DB::raw("CASE
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 3 AND 6 THEN 'Paud'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 7 AND 12 THEN 'Caberawit'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 13 AND 15 THEN 'Pra-remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) BETWEEN 16 AND 18 THEN 'Remaja'
                WHEN TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) >= 19 THEN 'Muda - mudi / Usia Nikah'
                ELSE 'Tidak dalam rentang usia'
            END AS status_kelas"),
            'data_peserta.tmpt_daerah',
            'tabel_daerah.nama_daerah',
            'data_peserta.tmpt_desa',
            'tabel_desa.nama_desa',
            'data_peserta.tmpt_kelompok',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.status_sambung',
            'data_peserta.status_pernikahan',
        ])
            ->join('tbl_pekerjaan', function ($join) {
                $join->on('tbl_pekerjaan.id', '=', DB::raw('CAST(data_peserta.pekerjaan AS UNSIGNED)'));
            })
            ->join('tabel_daerah', function ($join) {
                $join->on('tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'));
            })
            ->join('tabel_desa', function ($join) {
                $join->on('tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'));
            })
            ->join('tabel_kelompok', function ($join) {
                $join->on('tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'));
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'));
            })
            ->where('data_peserta.kode_cari_data', $request->kode_cari_data)
            ->when($kegiatan->tmpt_daerah, function ($query) use ($kegiatan) {
                return $query->where('data_peserta.tmpt_daerah', $kegiatan->tmpt_daerah);
            })
            ->when($kegiatan->tmpt_desa, function ($query) use ($kegiatan) {
                return $query->where('data_peserta.tmpt_desa', $kegiatan->tmpt_desa);
            })
            ->when($kegiatan->tmpt_kelompok, function ($query) use ($kegiatan) {
                return $query->where('data_peserta.tmpt_kelompok', $kegiatan->tmpt_kelompok);
            })
            ->first();

        if (!$peserta) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan atau data peserta presensi tidak bisa diakses di tempat sambung ini',
                'success' => false,
            ], 404);
        }

        if ($peserta->status_sambung != 1 || $peserta->status_pernikahan != 0) {
            return response()->json([
                'message' => 'Presensi Ditolak, Peserta sudah pindah sambung atau sudah menikah',
                'success' => false,
            ], 403);
        }

        $usiaOperator = $kegiatan->usia_operator;
        $usiaBatas = $kegiatan->usia_batas;

        if (!empty($usiaOperator) && !empty($usiaBatas)) {
            $usia = $peserta->usia;

            if (!eval("return {$usia} {$usiaOperator} {$usiaBatas};")) {
                return response()->json([
                    'message' => 'Peserta tidak memenuhi kriteria usia',
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

        $waktuToleransi = $waktuKegiatan->copy()->addMinutes(30);

        $isLate = now()->greaterThan($waktuToleransi);

        $presensi = new presensi();
        $presensi->id_kegiatan = $request->id_kegiatan;
        $presensi->id_peserta = $peserta->id;
        $presensi->id_petugas = $request->id_petugas;
        $presensi->status_presensi = $isLate ? "TELAT HADIR" : "HADIR";
        $presensi->waktu_presensi = now();
        $presensi->keterangan = $isLate ? "TELAT HADIR" : "HADIR";
        // $phone = $request->phone;
        // $countryCode = '62';

        // $templates = [
        //     'attendance' => "✅ *Absensi Berhasil Dicatat!*\n\n"
        //         . "📌 *Nama:* " . ($peserta->nama_lengkap ?? '*Peserta Tidak Diketahui*') . "\n"
        //         . "📅 *Tgl. Absen:* " . $presensi->waktu_presensi . "\n"
        //         . "📢 *Kegiatan:* " . ($kegiatan->nama_kegiatan ?? '*Tidak Diketahui*') . "\n"
        //         . "📍 *Tempat:* " . ($kegiatan->tmpt_kegiatan ?? '*Tidak Diketahui*') . "\n"
        //         . "🛠️ *Method:* Scan QR\n"
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
                'aktifitas' => "Absensi Scan QR - [{$peserta->id}] - [{$peserta->nama_lengkap}]",
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
                // 'updated_fields' => json_encode($Seender),
            ]);

            return response()->json([
                'message' => 'Presensi berhasil dicatat',
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

    public function updateDataWaSensus(Request $request)
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
            'kode_cari_data' => 'required',
            'no_telepon' => 'required|string|digits_between:8,13',
        ], $customMessages);

        $sensus = dataSensusPeserta::where('kode_cari_data', '=', $request->kode_cari_data)
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
                'no_telepon' => $request->no_telepon,
            ]);

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
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal mengupdate data sensus' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $category = $request->get('category', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = presensiKegiatan::select([
            'presensi_kegiatan.id',
            'presensi_kegiatan.kode_kegiatan',
            'presensi_kegiatan.nama_kegiatan',
            'presensi_kegiatan.tmpt_kegiatan',
            'presensi_kegiatan.tgl_kegiatan',
            'presensi_kegiatan.jam_kegiatan',
            'presensi_kegiatan.category',
            'presensi_kegiatan.type_kegiatan',
            DB::raw('(SELECT COUNT(*) FROM presensi WHERE presensi.id_kegiatan = presensi_kegiatan.id) as presensi_count'),
            'users.nama_lengkap AS operator',
        ])
            ->join('users', function ($join) {
                $join->on('users.id', '=', DB::raw('CAST(presensi_kegiatan.add_by_petugas AS UNSIGNED)'));
            });
        $model->orderByRaw('presensi_kegiatan.created_at IS NULL, presensi_kegiatan.created_at DESC');

        // Tambahkan pengecekan role_id
        // if (auth()->user()->role_id != 1) {
        //     // Jika bukan role_id 1, tambahkan filter berdasarkan add_by_petugas
        //     $model->where('presensi_kegiatan.add_by_petugas', auth()->user()->id);
        // }

        if (!is_null($category)) {
            $model->where('presensi_kegiatan.category', '=', $category);
        }

        if (!empty($keyword) && empty($kolom)) {
            $model->where(function ($q) use ($keyword) {
                $q->where('presensi_kegiatan.kode_kegiatan', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('presensi_kegiatan.nama_kegiatan', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_kegiatan') {
                $kolom = 'presensi_kegiatan.kode_kegiatan';
            } elseif ($kolom == 'nama_kegiatan') {
                $kolom = 'presensi_kegiatan.nama_kegiatan';
            } else {
                $kolom = 'presensi_kegiatan.nama_kegiatan';
            }

            $model->where($kolom, 'LIKE', '%' . $keyword . '%');
        }

        $presensi = $model->paginate($perPage);

        $presensi->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Data Presensi Berhasil Ditemukan',
            'data_presensi' => $presensi,
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
            'nama_kegiatan' => 'required|max:225',
            'tmpt_kegiatan' => 'required|max:225',
            'type_kegiatan' => 'required',
            'tgl_kegiatan' => 'required',
            'jam_kegiatan' => 'required',
            'category' => 'required',
            'expired_date_time' => 'required',
            'tmpt_daerah' => 'required',
            'tmpt_desa' => 'nullable',
            'tmpt_kelompok' => 'nullable',
            'usia_batas' => 'nullable',
            'usia_operator' => 'nullable',
            'add_by_petugas' => 'required',
        ], $customMessages);

        $isDuplicate = presensiKegiatan::where('tgl_kegiatan', $request->tgl_kegiatan)
            ->where('jam_kegiatan', $request->jam_kegiatan)
            ->where('type_kegiatan', $request->type_kegiatan)
            ->where('add_by_petugas', $request->add_by_petugas)
            ->exists();

        if ($isDuplicate) {
            return response()->json([
                'message' => 'Data kegiatan yang sama sudah terdaftar',
                'success' => false,
            ], 400);
        }

        $kodeKegiatan = Str::upper(Str::random(6));

        while (presensiKegiatan::where('kode_kegiatan', $kodeKegiatan)->exists()) {
            $kodeKegiatan = Str::upper(Str::random(6)); // Generate ulang jika sudah ada
        }

        $presensi = new presensiKegiatan();
        $presensi->kode_kegiatan = $kodeKegiatan;
        $presensi->nama_kegiatan = $request->nama_kegiatan;
        $presensi->tmpt_kegiatan = $request->tmpt_kegiatan;
        $presensi->type_kegiatan = $request->type_kegiatan;
        $presensi->tgl_kegiatan = $request->tgl_kegiatan;
        $presensi->jam_kegiatan = $request->jam_kegiatan;
        $presensi->category = $request->category;
        $presensi->expired_date_time = $request->expired_date_time;
        $presensi->tmpt_daerah = $request->tmpt_daerah;
        $presensi->tmpt_desa = $request->tmpt_desa;
        $presensi->tmpt_kelompok = $request->tmpt_kelompok;
        $presensi->usia_batas = $request->usia_batas;
        $presensi->usia_operator = $request->usia_operator;
        $presensi->add_by_petugas = $request->add_by_petugas;
        try {
            $presensi->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Kegiatan Presensi' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($presensi->created_at, $presensi->updated_at);

        return response()->json([
            'message' => 'Data Kegiatatan Presensi berhasil ditambahkan',
            'data_presensi' => $presensi,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $presensi = presensiKegiatan::select([
            'presensi_kegiatan.id',
            'presensi_kegiatan.kode_kegiatan',
            'presensi_kegiatan.nama_kegiatan',
            'presensi_kegiatan.tmpt_kegiatan',
            'presensi_kegiatan.type_kegiatan',
            'presensi_kegiatan.tgl_kegiatan',
            'presensi_kegiatan.jam_kegiatan',
            'presensi_kegiatan.expired_date_time',
            'presensi_kegiatan.usia_batas',
            'presensi_kegiatan.usia_operator',
            'presensi_kegiatan.category',
            'presensi_kegiatan.tmpt_daerah',
            'presensi_kegiatan.tmpt_desa',
            'presensi_kegiatan.tmpt_kelompok',
            'users.nama_lengkap AS operator',
        ])
            ->leftJoin('users', 'presensi_kegiatan.add_by_petugas', '=', 'users.id')
            ->where('presensi_kegiatan.id', $request->id)->first();

        unset($presensi->created_at, $presensi->updated_at);

        if (!empty($presensi)) {
            return response()->json([
                'message' => 'Sukses',
                'data_presensi' => $presensi,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Kegiatan tidak ditemukan',
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
            'nama_kegiatan' => 'required|max:225|unique:presensi_kegiatan,nama_kegiatan,' . $request->id . ',id',
            'tmpt_kegiatan' => 'required|max:225',
            'tgl_kegiatan' => 'required',
            'jam_kegiatan' => 'required',
            'category' => 'required',
            'expired_date_time' => 'required',
            'usia_batas' => 'nullable',
            'usia_operator' => 'nullable',
        ], $customMessages);

        $presensiKegiatan = presensiKegiatan::find($request->id);

        if ($presensiKegiatan) {
            // Cek apakah ada perubahan pada data yang ingin divalidasi sebagai duplikat
            $isDuplicateCheckRequired = (
                $presensiKegiatan->tgl_kegiatan !== $request->tgl_kegiatan ||
                $presensiKegiatan->jam_kegiatan !== $request->jam_kegiatan ||
                $presensiKegiatan->type_kegiatan !== $request->type_kegiatan
            );

            // Lakukan pengecekan duplikasi hanya jika ada perubahan
            if ($isDuplicateCheckRequired) {
                $isDuplicate = presensiKegiatan::where('tgl_kegiatan', $request->tgl_kegiatan)
                    ->where('jam_kegiatan', $request->jam_kegiatan)
                    ->where('type_kegiatan', $request->type_kegiatan)
                    ->where('add_by_petugas', $request->add_by_petugas)
                    ->exists();

                if ($isDuplicate) {
                    return response()->json([
                        'message' => 'Data kegiatan yang sama sudah terdaftar',
                        'success' => false,
                    ], 400);
                }
            }
        }

        $presensi = presensiKegiatan::where('id', '=', $request->id)
            ->first();

        if (!empty($presensi)) {
            try {
                $presensi->update([
                    'id' => $request->id,
                    'nama_kegiatan' => $request->nama_kegiatan,
                    'tmpt_kegiatan' => $request->tmpt_kegiatan,
                    'tgl_kegiatan' => $request->tgl_kegiatan,
                    'jam_kegiatan' => $request->jam_kegiatan,
                    'category' => $request->category,
                    'expired_date_time' => $request->expired_date_time,
                    'usia_batas' => $request->usia_batas,
                    'usia_operator' => $request->usia_operator,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Presensi' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Presensi berhasil diupdate',
                'data_presensi' => $presensi,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Presensi tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $presensi = presensiKegiatan::where('id', '=', $request->id)
            ->first();

        if (!empty($presensi)) {
            $existsInPresensi = presensi::where('id_kegiatan', '=', $request->id)->exists();

            if ($existsInPresensi) {
                return response()->json([
                    'message' => 'Data Kegiatan tidak dapat dihapus karena sudah terdaftar dan digunakan sebagai media absen',
                    'success' => false,
                ], 409);
            }

            try {
                presensiKegiatan::where('id', '=', $request->id)->delete();

                return response()->json([
                    'message' => 'Data Kegiatan berhasil dihapus',
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
            'message' => 'Data Presensi tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function record_presensi_bypass(Request $request)
    {
        $agent = new Agent();
        $userId = Auth::id();

        $request->validate([
            'kode_cari_data' => 'required|string',
            'id_kegiatan' => 'required',
            'id_petugas' => 'required',
            'status_presensi' => 'required',
            'keterangan' => 'nullable|string',
        ]);

        $kegiatan = presensiKegiatan::find($request->id_kegiatan);
        if (!$kegiatan) {
            return response()->json([
                'message' => 'Kegiatan tidak ditemukan',
                'success' => false,
            ], 404);
        }

        if (empty($kegiatan->tgl_kegiatan) || empty($kegiatan->jam_kegiatan)) {
            return response()->json([
                'message' => 'Tanggal atau jam kegiatan tidak valid.',
                'success' => false,
            ], 400);
        }

        $peserta = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.kode_cari_data',
            'data_peserta.nama_lengkap',
            DB::raw('TIMESTAMPDIFF(YEAR, data_peserta.tanggal_lahir, CURDATE()) AS usia'),
            'data_peserta.tmpt_daerah',
            'tabel_daerah.nama_daerah',
            'data_peserta.tmpt_desa',
            'tabel_desa.nama_desa',
            'data_peserta.tmpt_kelompok',
            'tabel_kelompok.nama_kelompok',
            'data_peserta.status_sambung',
            'data_peserta.status_pernikahan',
        ])
            ->join('tabel_daerah', function ($join) {
                $join->on('tabel_daerah.id', '=', DB::raw('CAST(data_peserta.tmpt_daerah AS UNSIGNED)'));
            })
            ->join('tabel_desa', function ($join) {
                $join->on('tabel_desa.id', '=', DB::raw('CAST(data_peserta.tmpt_desa AS UNSIGNED)'));
            })
            ->join('tabel_kelompok', function ($join) {
                $join->on('tabel_kelompok.id', '=', DB::raw('CAST(data_peserta.tmpt_kelompok AS UNSIGNED)'));
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'));
            })
            ->where('data_peserta.kode_cari_data', $request->kode_cari_data)
            ->when($kegiatan->tmpt_daerah, function ($query) use ($kegiatan) {
                return $query->where('data_peserta.tmpt_daerah', $kegiatan->tmpt_daerah);
            })
            ->when($kegiatan->tmpt_desa, function ($query) use ($kegiatan) {
                return $query->where('data_peserta.tmpt_desa', $kegiatan->tmpt_desa);
            })
            ->when($kegiatan->tmpt_kelompok, function ($query) use ($kegiatan) {
                return $query->where('data_peserta.tmpt_kelompok', $kegiatan->tmpt_kelompok);
            })
            ->first();

        if (!$peserta) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan atau data peserta presensi tidak bisa diakses di tempat sambung ini',
                'success' => false,
            ], 404);
        }

        if ($peserta->status_sambung != 1 || $peserta->status_pernikahan != 0) {
            return response()->json([
                'message' => 'Presensi Ditolak, Peserta sudah pindah sambung atau sudah menikah',
                'success' => false,
            ], 403);
        }

        $usiaOperator = $kegiatan->usia_operator;
        $usiaBatas = $kegiatan->usia_batas;

        if (!empty($usiaOperator) && !empty($usiaBatas)) {
            $usia = $peserta->usia;

            if (!eval("return {$usia} {$usiaOperator} {$usiaBatas};")) {
                return response()->json([
                    'message' => 'Peserta tidak memenuhi kriteria usia',
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
        $presensi->id_kegiatan = $request->id_kegiatan;
        $presensi->id_peserta = $peserta->id;
        $presensi->id_petugas = $request->id_petugas;
        $presensi->status_presensi = $request->status_presensi;
        $presensi->waktu_presensi = now();
        $presensi->keterangan = $request->keterangan;

        try {
            $presensi->save();

            logs::create([
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => "Absensi ByPass - [{$peserta->id}] - [{$peserta->nama_lengkap}]",
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ]);

            return response()->json([
                'message' => 'Presensi berhasil dicatat',
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
