<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\dataCai;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\presensi;
use App\Models\presensiKegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\logs;
use App\Services\FonnteService;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;

class dataCaiController extends Controller
{
    protected $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    public function list_nama_peserta()
    {
        $cai = dataCai::select('kode_cari_data', 'nama_lengkap')
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
            'data' => $cai,
        ], 200);
    }

    public function record_presensi_qrcode(Request $request)
    {
        $agent = new Agent();
        $userId = Auth::id();

        $request->validate([
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

        $peserta = dataCai::select([
            'data_cai.id',
            'data_cai.kode_cari_data',
            'data_cai.nama_lengkap',
            'data_cai.jenis_kelamin',
            'data_cai.tmpt_daerah',
            'tabel_daerah.nama_daerah',
            'data_cai.tmpt_desa',
            'tabel_desa.nama_desa',
            'data_cai.tmpt_kelompok',
            'tabel_kelompok.nama_kelompok',
            'data_cai.status_sambung',
            'data_cai.status_pernikahan',
        ])
            ->join('tabel_daerah', function ($join) {
                $join->on('tabel_daerah.id', '=', DB::raw('CAST(data_cai.tmpt_daerah AS UNSIGNED)'));
            })
            ->join('tabel_desa', function ($join) {
                $join->on('tabel_desa.id', '=', DB::raw('CAST(data_cai.tmpt_desa AS UNSIGNED)'));
            })
            ->join('tabel_kelompok', function ($join) {
                $join->on('tabel_kelompok.id', '=', DB::raw('CAST(data_cai.tmpt_kelompok AS UNSIGNED)'));
            })
            ->where('data_cai.kode_cari_data', $request->kode_cari_data)
            ->when($kegiatan->tmpt_daerah, function ($query) use ($kegiatan) {
                return $query->where('data_cai.tmpt_daerah', $kegiatan->tmpt_daerah);
            })
            ->when($kegiatan->tmpt_desa, function ($query) use ($kegiatan) {
                return $query->where('data_cai.tmpt_desa', $kegiatan->tmpt_desa);
            })
            ->when($kegiatan->tmpt_kelompok, function ($query) use ($kegiatan) {
                return $query->where('data_cai.tmpt_kelompok', $kegiatan->tmpt_kelompok);
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
        $presensi->id_peserta = $peserta->kode_cari_data;
        $presensi->id_petugas = $request->id_petugas;
        $presensi->status_presensi = $isLate ? "TELAT HADIR" : "HADIR";
        $presensi->waktu_presensi = now();
        $presensi->keterangan = $isLate ? "TELAT HADIR" : "HADIR";

        try {
            $presensi->save();

            logs::create([
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => "Absensi CAI Scan QR - [{$peserta->id}] - [{$peserta->nama_lengkap}]",
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

        $peserta = dataCai::select([
            'data_cai.id',
            'data_cai.kode_cari_data',
            'data_cai.nama_lengkap',
            'data_cai.tmpt_daerah',
            'tabel_daerah.nama_daerah',
            'data_cai.tmpt_desa',
            'tabel_desa.nama_desa',
            'data_cai.tmpt_kelompok',
            'tabel_kelompok.nama_kelompok',
        ])
            ->join('tabel_daerah', function ($join) {
                $join->on('tabel_daerah.id', '=', DB::raw('CAST(data_cai.tmpt_daerah AS UNSIGNED)'));
            })
            ->join('tabel_desa', function ($join) {
                $join->on('tabel_desa.id', '=', DB::raw('CAST(data_cai.tmpt_desa AS UNSIGNED)'));
            })
            ->join('tabel_kelompok', function ($join) {
                $join->on('tabel_kelompok.id', '=', DB::raw('CAST(data_cai.tmpt_kelompok AS UNSIGNED)'));
            })
            ->where('data_cai.kode_cari_data', $request->kode_cari_data)
            ->when($kegiatan->tmpt_daerah, function ($query) use ($kegiatan) {
                return $query->where('data_cai.tmpt_daerah', $kegiatan->tmpt_daerah);
            })
            ->when($kegiatan->tmpt_desa, function ($query) use ($kegiatan) {
                return $query->where('data_cai.tmpt_desa', $kegiatan->tmpt_desa);
            })
            ->when($kegiatan->tmpt_kelompok, function ($query) use ($kegiatan) {
                return $query->where('data_cai.tmpt_kelompok', $kegiatan->tmpt_kelompok);
            })
            ->first();

        if (!$peserta) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan atau data peserta presensi tidak bisa diakses di tempat sambung ini',
                'success' => false,
            ], 404);
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
        $presensi->id_peserta = $peserta->kode_cari_data;
        $presensi->id_petugas = $request->id_petugas;
        $presensi->status_presensi = $request->status_presensi;
        $presensi->waktu_presensi = now();
        $presensi->keterangan = $request->keterangan;

        try {
            $presensi->save();

            logs::create([
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => "Absensi CAI ByPass - [{$peserta->id}] - [{$peserta->nama_lengkap}]",
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

    public function getPresensiReport(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

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
        $pesertaQuery = dataCai::select([
            'data_cai.id',
            'data_cai.kode_cari_data',
            'data_cai.nama_lengkap',
            'data_cai.jenis_kelamin',
            'presensi.id AS presensi_id',
            'presensi.status_presensi',
            'presensi.keterangan',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'presensi.waktu_presensi',
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_cai.tmpt_daerah AS UNSIGNED)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_cai.tmpt_desa AS UNSIGNED)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_cai.tmpt_kelompok AS UNSIGNED)'))
            ->leftJoin('presensi', function ($join) use ($id_kegiatan) {
                $join->on('data_cai.kode_cari_data', '=', 'presensi.id_peserta')
                    ->where('presensi.id_kegiatan', '=', $id_kegiatan);
            })
            ->when($kegiatan->tmpt_daerah, fn($q) => $q->where('data_cai.tmpt_daerah', $kegiatan->tmpt_daerah))
            ->when($kegiatan->tmpt_desa, fn($q) => $q->where('data_cai.tmpt_desa', $kegiatan->tmpt_desa))
            ->when($kegiatan->tmpt_kelompok, fn($q) => $q->where('data_cai.tmpt_kelompok', $kegiatan->tmpt_kelompok));

        // Filter keyword
        if ($keyword) {
            $pesertaQuery->where(function ($q) use ($keyword) {
                $q->where('data_cai.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('data_cai.jenis_kelamin', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('presensi.status_presensi', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%');
            });
        }

        // Hitung statistik langsung dari database
        $statistics = dataCai::leftJoin('presensi', function ($join) use ($id_kegiatan) {
            $join->on('data_cai.kode_cari_data', '=', 'presensi.id_peserta')
                ->where('presensi.id_kegiatan', '=', $id_kegiatan);
        })
            ->leftJoin('tabel_daerah', 'tabel_daerah.id', '=', 'data_cai.tmpt_daerah')
            ->leftJoin('tabel_desa', 'tabel_desa.id', '=', 'data_cai.tmpt_desa')
            ->leftJoin('tabel_kelompok', 'tabel_kelompok.id', '=', 'data_cai.tmpt_kelompok')
            ->selectRaw('
            COUNT(CASE WHEN presensi.status_presensi = "HADIR" THEN 1 END) AS hadir,
            COUNT(CASE WHEN presensi.status_presensi = "TELAT HADIR" THEN 1 END) AS telat_hadir,
            COUNT(CASE WHEN presensi.status_presensi = "IZIN" THEN 1 END) AS izin,
            COUNT(CASE WHEN presensi.status_presensi = "SAKIT" THEN 1 END) AS sakit,
            COUNT(CASE WHEN presensi.status_presensi IS NULL THEN 1 ELSE NULL END) AS alfa
        ')
            ->when($kegiatan->tmpt_daerah, fn($q) => $q->where('data_cai.tmpt_daerah', $kegiatan->tmpt_daerah))
            ->when($kegiatan->tmpt_desa, fn($q) => $q->where('data_cai.tmpt_desa', $kegiatan->tmpt_desa))
            ->when($kegiatan->tmpt_kelompok, fn($q) => $q->where('data_cai.tmpt_kelompok', $kegiatan->tmpt_kelompok))
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($query) use ($keyword) {
                    $query->where('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%');
                });
            })
            ->first();

        // Transformasi data
        $allData = $pesertaQuery->get();
        $transformedData = $allData->map(fn($peserta) => [
            'id_peserta' => $peserta->id,
            'nama_lengkap' => $peserta->nama_lengkap,
            'jenis_kelamin' => $peserta->jenis_kelamin,
            'status_presensi' => $peserta->presensi_id ? $peserta->status_presensi : 'alfa/tidak hadir',
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

    public function list_data_peserta_cai(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $jenisKelamin = $request->get('jenis_kelamin', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = dataCai::select([
            'data_cai.id',
            'data_cai.kode_cari_data',
            'data_cai.nama_lengkap',
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
            'tabel_kelompok.nama_kelompok',
            'data_cai.created_at',
        ])
            ->join('tabel_daerah', 'tabel_daerah.id', '=', DB::raw('CAST(data_cai.tmpt_daerah AS UNSIGNED)'))
            ->join('tabel_desa', 'tabel_desa.id', '=', DB::raw('CAST(data_cai.tmpt_desa AS UNSIGNED)'))
            ->join('tabel_kelompok', 'tabel_kelompok.id', '=', DB::raw('CAST(data_cai.tmpt_kelompok AS UNSIGNED)'));

        // Apply orderByRaw before executing the query
        $query->orderByRaw('data_cai.created_at IS NULL, data_cai.created_at DESC');

        if (!is_null($jenisKelamin)) {
            $query->where('data_cai.jenis_kelamin', '=', $jenisKelamin);
        }

        if (!empty($keyword) && empty($kolom)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('data_cai.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('data_cai.kode_cari_data', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'kode_cari_data') {
                $kolom = 'data_cai.kode_cari_data';
            } else {
                $kolom = 'data_cai.kode_cari_data';
            }

            $query->where($kolom, 'LIKE', '%' . $keyword . '%');
        }

        $cai = $query->paginate($perPage);

        $cai->appends([
            'per-page' => $perPage,
        ]);

        return response()->json([
            'message' => 'Data Ditemukan',
            'data_cai' => $cai,
            'success' => true,
        ], 200);
    }

    public function create_data_peserta_cai(Request $request)
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
            'nama_lengkap' => 'required|string|unique:data_cai',
            'status_utusan' => 'required',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'required|integer|digits_between:1,5',
            'tmpt_kelompok' => 'required|integer|digits_between:1,5',
            'img' => 'nullable|image|mimes:jpg,png|max:5120',
        ], $customMessages);

        $tabel_cai = new dataCai();

        $tanggalSekarang = Carbon::now();
        $prefix = 'CAI';

        do {
            $kodeUnik = $prefix . $tanggalSekarang->format('ymdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        } while (\App\Models\dataCai::where('kode_cari_data', $kodeUnik)->exists());

        $tabel_cai->kode_cari_data = $kodeUnik;
        $tabel_cai->nama_lengkap = ucwords(strtolower($request->nama_lengkap));
        $tabel_cai->status_utusan = $request->status_utusan;
        $tabel_cai->tmpt_daerah = $request->tmpt_daerah;
        $tabel_cai->tmpt_desa = $request->tmpt_desa;
        $tabel_cai->tmpt_kelompok = $request->tmpt_kelompok;
        $tabel_cai->tahun = date('Y');
        $tabel_cai->img = $request->img;

        if ($request->hasFile('img')) {
            $foto = $request->file('img'); // Get the uploaded file

            // Generate a unique filename
            $namaFile = Str::slug($tabel_cai->kode_cari_data) . '.' . $foto->getClientOriginalExtension();

            // Save the file to the 'public/images/sensus' directory
            $path = $foto->storeAs('public/images/sensus', $namaFile);

            // Update the database record
            $tabel_cai->img = $path;
        } else {
            $tabel_cai->img = null; // Handle cases where no file is uploaded
        }

        if (!$tabel_daerah || !$tabel_desa || !$tabel_kelompok) {
            return response()->json([
                'message' => 'Validasi gagal',
                'success' => false,
                'errors' => [
                    'tmpt_daerah' => !$tabel_daerah ? 'Daerah tidak ditemukan' : null,
                    'tmpt_desa' => !$tabel_desa ? 'Desa tidak ditemukan' : null,
                    'tmpt_kelompok' => !$tabel_kelompok ? 'Kelompok tidak ditemukan' : null,
                ]
            ], 404);
        }

        try {

            $tabel_cai->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Create Data Peserta Cai - [' . $tabel_cai->id . '] - [' . $tabel_cai->nama_lengkap . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ];
            logs::create($logAccount);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Cai' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($tabel_cai->created_at, $tabel_cai->updated_at);

        return response()->json([
            'message' => 'Data Cai berhasil ditambahkan',
            'data_cai' => $tabel_cai,
            'success' => true,
        ], 200);
    }

    public function edit_data_peserta_cai(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $cai = dataCai::select([
            'data_cai.id',
            'data_cai.kode_cari_data',
            'data_cai.nama_lengkap',
            'data_cai.jenis_kelamin',
            'data_cai.status_utusan',
            'tabel_daerah.id as id_daerah',
            'tabel_daerah.nama_daerah',
            'tabel_desa.id as id_desa',
            'tabel_desa.nama_desa',
            'tabel_kelompok.id as id_kelompok',
            'tabel_kelompok.nama_kelompok',
            'data_cai.img',
        ])->join('tabel_daerah', function ($join) {
            $join->on('tabel_daerah.id', '=', DB::raw('CAST(data_cai.tmpt_daerah AS UNSIGNED)'));
        })->join('tabel_desa', function ($join) {
            $join->on('tabel_desa.id', '=', DB::raw('CAST(data_cai.tmpt_desa AS UNSIGNED)'));
        })->join('tabel_kelompok', function ($join) {
            $join->on('tabel_kelompok.id', '=', DB::raw('CAST(data_cai.tmpt_kelompok AS UNSIGNED)'));
        })->where('data_cai.id', '=', $request->id)->first();

        if ($cai) {
            // Generate the correct URL for the image
            $cai->img_url = $cai->img
                ? asset('storage/' . str_replace('public/', '', $cai->img))
                : null;

            unset($cai->created_at, $cai->updated_at);

            return response()->json([
                'message' => 'Data Peserta Ditemukan',
                'data_cai' => $cai,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Cai tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update_data_peserta_cai(Request $request)
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
            'nama_lengkap' => 'sometimes|required|string|unique:data_cai,nama_lengkap,' . $request->id . ',id',
            'status_utusan' => 'required',
        ], $customMessages);

        $cai = dataCai::where('id', '=', $request->id)
            ->first();

        if (!$cai) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
                'success' => false,
            ], 404);
        }

        try {

            $originalData = $cai->getOriginal();

            $cai->fill([
                'nama_lengkap' => ucwords(strtolower($request->nama_lengkap)),
                'status_utusan' => $request->status_utusan,
                'tmpt_daerah' => $request->tmpt_daerah,
                'tmpt_desa' => $request->tmpt_desa,
                'tmpt_kelompok' => $request->tmpt_kelompok,
                'img' => $request->img,
            ]);

            if ($request->hasFile('img')) {
                $request->validate([
                    'img' => 'nullable|image|mimes:jpg,png|max:5120',
                ], $customMessages);
                $oldImgPath = storage_path('public/images/sensus/' . $cai->img);

                // Hapus file lama jika ada
                if (file_exists($oldImgPath) && $cai->img) {
                    unlink($oldImgPath);
                }

                // Save the file to the 'public/images/sensus' directory
                $newImg = $request->file('img');
                $namaFile = Str::slug($cai->nama_lengkap) . '.' . $newImg->getClientOriginalExtension();
                $path = $newImg->storeAs('public/images/sensus', $namaFile);
                $cai->img = $path;
            }

            $updatedFields = [];
            foreach ($cai->getDirty() as $field => $newValue) {
                $oldValue = $originalData[$field] ?? null; // Ambil nilai lama
                $updatedFields[] = "$field: [$oldValue] -> [$newValue]";
            }

            $cai->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Update Data Cai - [' . $cai->id . '] - [' . $cai->nama_lengkap . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
                'updated_fields' => json_encode($updatedFields), // Simpan sebagai JSON
            ];
            logs::create($logAccount);

            return response()->json([
                'message' => 'Data Cai berhasil diupdate',
                'data_cai' => $cai,
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal mengupdate data Cai' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function delete_data_peserta_cai(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $cai = dataCai::where('id', '=', $request->id)
            ->first();

        if (!empty($cai)) {
            $existsInPresensi = presensi::where('id_peserta', $request->kode_cari_data)->exists();

            if ($existsInPresensi) {
                return response()->json([
                    'message' => 'Data Peserta Cai tidak dapat dihapus karena sudah terdaftar dan digunakan di tabel lain',
                    'success' => false,
                ], 409);
            }

            try {
                // Hapus file gambar jika ada
                if (!empty($cai->img)) {
                    $filePath = storage_path('app/' . $cai->img); // Path lengkap file
                    if (file_exists($filePath)) {
                        unlink($filePath); // Hapus file dari folder
                    }
                }

                $deletedData = $cai->toArray();

                // Lanjutkan untuk menghapus data Peserta Didik
                $cai->delete();

                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Delete Data Peserta Cai - [' . $deletedData['id'] . '] - [' . $deletedData['nama_lengkap'] . ']',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    'engine_agent' => $request->header('user-agent'),
                ];
                logs::create($logAccount);

                return response()->json([
                    'message' => 'Data Peserta Cai berhasil dihapus beserta file terkait',
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
}
