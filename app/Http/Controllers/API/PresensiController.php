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

class PresensiController extends Controller
{
    public function getPresensiReport(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $customMessages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'min' => ':attribute harus memiliki setidaknya :min karakter.',
            'regex' => ':attribute harus mengandung setidaknya satu huruf kapital dan satu angka.',
            'numeric' => ':attribute harus berupa angka.',
            'digits_between' => ':attribute harus memiliki panjang antara :min dan :max digit.',
        ];

        // Validate input
        $request->validate([
            'id_kegiatan' => 'required|numeric',
        ], $customMessages);

        $id_kegiatan = $request->id_kegiatan;

        $kegiatan = PresensiKegiatan::find($id_kegiatan);

        if (!$kegiatan) {
            return response()->json([
                'message' => 'Kegiatan tidak ditemukan',
                'success' => false,
            ], 404);
        }

        $pesertaQuery = dataSensusPeserta::select([
            'data_peserta.id',
            'data_peserta.nama_lengkap',
            'data_peserta.tanggal_lahir',
            'data_peserta.jenis_kelamin',
            'presensi.id AS presensi_id',
            'presensi.status_presensi',
            'presensi.keterangan',
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
            ->leftJoin('presensi', function ($join) use ($id_kegiatan) {
                $join->on('data_peserta.id', '=', 'presensi.id_peserta')
                    ->where('presensi.id_kegiatan', '=', $id_kegiatan);
            })
            ->when($kegiatan->tmpt_daerah, function ($query) use ($kegiatan) {
                $query->where('data_peserta.tmpt_daerah', $kegiatan->tmpt_daerah);
            })
            ->when($kegiatan->tmpt_desa, function ($query) use ($kegiatan) {
                $query->where('data_peserta.tmpt_desa', $kegiatan->tmpt_desa);
            })
            ->when($kegiatan->tmpt_kelompok, function ($query) use ($kegiatan) {
                $query->where('data_peserta.tmpt_kelompok', $kegiatan->tmpt_kelompok);
            });

        // Apply filtering if a keyword is provided
        if (!empty($keyword)) {
            $pesertaQuery->where(function ($q) use ($keyword) {
                $q->where('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('data_peserta.jenis_kelamin', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('presensi.status_presensi', 'LIKE', '%' . $keyword . '%');
            });
        }

        // Retrieve all matching data without pagination
        $reportData = $pesertaQuery->get();

        // Transform the data for the response
        $transformedData = $reportData->map(function ($peserta) {
            return [
                'id_peserta' => $peserta->id,
                'nama_lengkap' => $peserta->nama_lengkap,
                'tanggal_lahir' => $peserta->tanggal_lahir,
                'jenis_kelamin' => $peserta->jenis_kelamin,
                'status_presensi' => $peserta->presensi_id ? $peserta->status_presensi : 'alfa/tidak hadir',
                'keterangan' => $peserta->keterangan,
            ];
        });

        // Initialize counters for the statistics
        $statistics = [
            'hadir' => 0,
            'telat_hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alfa' => 0,
        ];

        // Update statistics based on the attendance status
        $transformedData->each(function ($peserta) use (&$statistics) {
            $status_presensi = $peserta['status_presensi'];

            switch ($status_presensi) {
                case 'HADIR':
                    $statistics['hadir']++;
                    break;
                case 'TELAT HADIR':
                    $statistics['telat_hadir']++;
                    break;
                case 'IZIN':
                    $statistics['izin']++;
                    break;
                case 'SAKIT':
                    $statistics['sakit']++;
                    break;
                default:
                    $statistics['alfa']++;
            }
        });

        return response()->json([
            'message' => 'Data presensi berhasil diambil',
            'data_presensi_peserta' => $transformedData,
            'statistics' => $statistics,
            'success' => true,
        ], 200);
    }

    public function record_presensi_qrcode(Request $request)
    {
        $request->validate([
            'kode_cari_data' => 'required|string',
            'id_kegiatan' => 'required',
            'id_petugas' => 'required',
        ]);

        // Get the event details
        $kegiatan = presensiKegiatan::find($request->id_kegiatan);

        if (!$kegiatan) {
            return response()->json([
                'message' => 'Kegiatan tidak ditemukan',
                'success' => false,
            ], 404);
        }

        // Mendapatkan waktu saat ini
        $currentTime = Carbon::now();

        // Menggabungkan tgl_kegiatan dan jam_kegiatan menjadi satu objek Carbon
        $waktuKegiatan = Carbon::parse($kegiatan->tgl_kegiatan . ' ' . $kegiatan->jam_kegiatan);

        // Menentukan waktu mulai presensi (90 menit sebelum waktu kegiatan)
        $waktuMulaiPresensi = $waktuKegiatan->copy()->subMinutes(90);

        // Cek apakah waktu saat ini sudah mencapai atau melewati waktu kegiatan
        if ($currentTime->lt($waktuMulaiPresensi)) {
            return response()->json([
                'message' => 'Presensi belum bisa dilakukan, tunggu hingga waktu kegiatan dimulai pada tanggal ' . $kegiatan->tgl_kegiatan . ' jam ' . $kegiatan->jam_kegiatan . ' waktu setempat',
                'success' => false,
            ], 403);
        }

        // Check if the current time is past the expired_date_time
        if (now()->greaterThan($kegiatan->expired_date_time)) {
            return response()->json([
                'message' => 'Presensi sudah tidak bisa dilakukan, waktu telah berakhir',
                'success' => false,
            ], 403);
        }

        // Fetch the participant data with filtering by location if required
        $peserta = dataSensusPeserta::select([
            'data_peserta.id',
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
            'tabel_daerah.id AS daerah_id',
            'tabel_daerah.nama_daerah',
            'tabel_desa.id AS desa_id',
            'tabel_desa.nama_desa',
            'tabel_kelompok.id AS kelompok_id',
            'tabel_kelompok.nama_kelompok',
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
        })
            ->where('data_peserta.kode_cari_data', $request->kode_cari_data);

        // Apply location filters based on event settings in kegiatan
        if ($kegiatan->tmpt_daerah || $kegiatan->tmpt_desa || $kegiatan->tmpt_kelompok) {
            $peserta->where(function ($query) use ($kegiatan) {
                // Check each location filter only if it's set in kegiatan
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

        // Retrieve the first matching participant
        $peserta = $peserta->first();

        // Check if participant data is valid
        if (!$peserta) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan atau data peserta presensi tidak bisa diakses di tempat sambung ini',
                'success' => false,
            ], 404);
        }

        // Check age restriction
        $usiaOperator = $kegiatan->usia_operator;
        $usiaBatas = $kegiatan->usia_batas;

        if (!empty($usiaOperator) && !empty($usiaBatas)) {
            $usia = $peserta->usia;

            // Evaluasi kondisi berdasarkan operator usia
            if (!eval("return {$usia} {$usiaOperator} {$usiaBatas};")) {
                return response()->json([
                    'message' => 'Peserta tidak memenuhi kriteria usia',
                    'success' => false,
                ], 403);
            }
        }

        // Check if the participant has already attended
        $existingPresensi = presensi::where('id_kegiatan', $request->id_kegiatan)
            ->where('id_peserta', $peserta->id)
            ->first();

        if ($existingPresensi) {
            return response()->json([
                'message' => 'Peserta sudah melakukan presensi',
                'success' => false,
            ], 409);
        }

        // Menentukan waktu toleransi keterlambatan (30 menit setelah waktu kegiatan dimulai)
        $waktuToleransi = $waktuKegiatan->copy()->addMinutes(30);

        // Determine if the attendance is late
        $isLate = now()->greaterThan($waktuToleransi);

        $presensi = new presensi();
        $presensi->id_kegiatan = $request->id_kegiatan;
        $presensi->id_peserta = $peserta->id;
        $presensi->id_petugas = $request->id_petugas;
        $presensi->status_presensi = $isLate ? "TELAT HADIR" : "HADIR";
        $presensi->waktu_presensi = now();
        $presensi->keterangan = $isLate ? "TELAT HADIR" : "HADIR";
        $presensi->save();

        return response()->json([
            'message' => 'Presensi berhasil dicatat',
            'data_presensi' => $peserta,
            'success' => true,
        ], 200);
    }

    public function record_presensi_manual(Request $request)
    {
        $request->validate([
            'kode_kegiatan' => 'required|string',
            'status_presensi' => 'required',
            'keterangan' => 'required',
            'nama_lengkap' => 'required',
            'tanggal_lahir' => 'required',
            'nama_ortu' => 'required',
        ]);

        // Cari detail kegiatan berdasarkan kode_kegiatan
        $kegiatan = presensiKegiatan::where('kode_kegiatan', $request->kode_kegiatan)->first();

        if (!$kegiatan) {
            return response()->json([
                'message' => 'Kegiatan tidak ditemukan',
                'success' => false,
            ], 404);
        }

        // Mendapatkan waktu saat ini
        $currentTime = Carbon::now();

        // Menggabungkan tgl_kegiatan dan jam_kegiatan menjadi satu objek Carbon
        $waktuKegiatan = Carbon::parse($kegiatan->tgl_kegiatan . ' ' . $kegiatan->jam_kegiatan);

        // Cek apakah waktu saat ini sudah mencapai atau melewati waktu kegiatan
        if ($currentTime->lt($waktuKegiatan)) {
            return response()->json([
                'message' => 'Presensi belum bisa dilakukan, tunggu hingga waktu kegiatan dimulai pada tanggal ' . $kegiatan->tgl_kegiatan . ' jam ' . $kegiatan->jam_kegiatan . ' waktu setempat',
                'success' => false,
            ], 403);
        }

        // Check if the current time is past the expired_date_time
        if (now()->greaterThan($kegiatan->expired_date_time)) {
            return response()->json([
                'message' => 'Presensi sudah tidak bisa dilakukan, waktu telah berakhir',
                'success' => false,
            ], 403);
        }

        // Fetch the participant data with filtering by location if required
        $peserta = dataSensusPeserta::select([
            'data_peserta.id',
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
            'tabel_daerah.id AS daerah_id',
            'tabel_daerah.nama_daerah',
            'tabel_desa.id AS desa_id',
            'tabel_desa.nama_desa',
            'tabel_kelompok.id AS kelompok_id',
            'tabel_kelompok.nama_kelompok',
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
            ->join('tbl_pekerjaan', function ($join) {
                $join->on('tbl_pekerjaan.id', '=', DB::raw('CAST(data_peserta.pekerjaan AS UNSIGNED)'));
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', DB::raw('CAST(data_peserta.user_id AS UNSIGNED)'));
            })
            ->where('data_peserta.nama_lengkap', $request->nama_lengkap)
            ->where('data_peserta.tanggal_lahir', $request->tanggal_lahir)
            ->where(function ($query) use ($request) {
                $query->where('data_peserta.nama_ayah', $request->nama_ortu)
                    ->orWhere('data_peserta.nama_ibu', $request->nama_ortu);
            });

        // Apply location filters based on event settings in kegiatan
        if ($kegiatan->tmpt_daerah || $kegiatan->tmpt_desa || $kegiatan->tmpt_kelompok) {
            $peserta->where(function ($query) use ($kegiatan) {
                // Check each location filter only if it's set in kegiatan
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

        // Retrieve the first matching participant
        $peserta = $peserta->first();

        // Check if participant data is valid
        if (!$peserta) {
            return response()->json([
                'message' => 'Peserta tidak ditemukan atau data peserta presensi tidak bisa diakses di tempat sambung ini',
                'success' => false,
            ], 404);
        }

        // Check age restriction
        $usiaOperator = $kegiatan->usia_operator;
        $usiaBatas = $kegiatan->usia_batas;

        if (!empty($usiaOperator) && !empty($usiaBatas)) {
            $usia = $peserta->usia;

            // Evaluasi kondisi berdasarkan operator usia
            if (!eval("return {$usia} {$usiaOperator} {$usiaBatas};")) {
                return response()->json([
                    'message' => 'Peserta tidak memenuhi kriteria usia',
                    'success' => false,
                ], 403);
            }
        }

        // Check if the participant has already attended
        $existingPresensi = presensi::where('id_kegiatan', $request->id_kegiatan)
            ->where('id_peserta', $peserta->id)
            ->first();

        if ($existingPresensi) {
            return response()->json([
                'message' => 'Peserta sudah melakukan presensi',
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
        $presensi->save();

        return response()->json([
            'message' => 'Presensi berhasil dicatat',
            'data_presensi' => $peserta,
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

        $model = presensiKegiatan::select([
            'presensi_kegiatan.id',
            'presensi_kegiatan.kode_kegiatan',
            'presensi_kegiatan.nama_kegiatan',
            'presensi_kegiatan.tmpt_kegiatan',
            'presensi_kegiatan.tgl_kegiatan',
            'presensi_kegiatan.jam_kegiatan',
            DB::raw('(SELECT COUNT(*) FROM presensi WHERE presensi.id_kegiatan = presensi_kegiatan.id) as presensi_count'),
            'users.nama_lengkap AS operator',
        ])
            ->join('users', function ($join) {
                $join->on('users.id', '=', DB::raw('CAST(presensi_kegiatan.add_by_petugas AS UNSIGNED)'));
            });

        // Tambahkan pengecekan role_id
        if (auth()->user()->role_id != 1) {
            // Jika bukan role_id 1, tambahkan filter berdasarkan add_by_petugas
            $model->where('presensi_kegiatan.add_by_petugas', auth()->user()->id);
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
}
