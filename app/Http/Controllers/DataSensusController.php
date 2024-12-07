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
                'message' => 'Data Sensus tidak ditemukan' . $response->status(),
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
            'users.username AS user_petugas',
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
            // Change this part to search based on the requested fields
            ->where('data_peserta.nama_lengkap', $request->nama_lengkap)
            ->where('data_peserta.tanggal_lahir', $request->tanggal_lahir)
            ->where(function ($query) use ($request) {
                $query->where('data_peserta.nama_ayah', $request->nama_ortu)
                    ->orWhere('data_peserta.nama_ibu', $request->nama_ortu);
            })
            ->first();

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
}
