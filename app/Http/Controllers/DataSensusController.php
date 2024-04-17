<?php

namespace App\Http\Controllers;

use App\Models\dataSensusPeserta;
use App\Models\logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

class DataSensusController extends Controller
{
    public function cari_data(Request $request)
    {
        $agent = new Agent();

        $request->validate([
            'kode_cari_data' => 'required',
        ]);

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

            $getDaata = Http::get("http://ip-api.com/json/{$ipFromResponse}")->json();

            // Buat log dengan informasi IP dari respons
            $logAccount = [
                'user_id' => 0,
                'ip_address' => $ipFromResponse,
                'aktifitas' => '[Cari Data Sensus]',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'location_info' => $getDaata,
                'latitude' => $getDaata['lat'],
                'longitude' => $getDaata['lon'],
            ];
            logs::create($logAccount);
        } else {
            return response()->json([
                'message' => 'Data Sensus tidak ditemukan'.$response->status(),
                'success' => false,
            ], 200);
        }

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
            'tabel_daerah.nama_daerah',
            'tabel_desa.nama_desa',
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
        })->where('data_peserta.kode_cari_data', '=', $request->kode_cari_data)->first();

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
                'message' => 'Data Sensus tidak ditemukan'.$exception->getMessage(),
            ], 200);
        }
    }
}
