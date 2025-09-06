<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;
use App\Models\logs;

class SambaraController extends Controller
{
    public function infoPajak(Request $request)
    {
        $agent = new Agent();
        try {
            // body request sesuai format API
            $body = [
                "where" => [
                    ["objek_pajak_no_polisi1", "=", $request->objek_pajak_no_polisi1 ?? ""],
                    ["objek_pajak_no_polisi2", "=", $request->objek_pajak_no_polisi2 ?? ""],
                    ["objek_pajak_no_polisi3", "=", $request->objek_pajak_no_polisi3 ?? ""],
                    ["objek_pajak_kd_plat", "=", $request->objek_pajak_kd_plat ?? ""],
                ],
                "bayar_kedepan" => $request->bayar_kedepan ?? "",
            ];

            // request ke API eksternal
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://apisakti.bapenda.jabarprov.go.id/api/utilities/info-pajak', $body);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data dari API',
                    'error'   => $response->body()
                ], $response->status());
            }

            $result = $response->json();

            // ambil data dari response API (langsung dari $result['data'])
            $dataApi = $result['data'] ?? null;

            if ($dataApi && is_array($dataApi)) {
                $noIdentitas   = $dataApi['no_identitas'] ?? '-';
                $namaPemilik   = $dataApi['nm_pemilik'] ?? '-';
                $alamatPemilik = $dataApi['al_pemilik'] ?? '-';
            } else {
                $noIdentitas = $namaPemilik = $alamatPemilik = '-';
            }

            // simpan log
            Logs::create([
                'user_id'       => 0,
                'ip_address'    => $request->ip(),
                'aktifitas'     => "Cek data Sambara - [no_identitas: {$noIdentitas}] - [nm_pemilik: {$namaPemilik}] - [al_pemilik: {$alamatPemilik}]",
                'status_logs'   => 'successfully',
                'browser'       => $agent->browser(),
                'os'            => $agent->platform(),
                'device'        => $agent->device(),
                'engine_agent'  => $request->header('user-agent'),
                'updated_fields' => json_encode($body),
            ]);

            // balikin data dari API
            return response()->json([
                'success' => true,
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function cekBisaBayarKedepan(Request $request)
    {
        try {
            // validasi simple
            $validated = $request->validate([
                'tg_proses_tetap' => 'required|date_format:Y-m-d',
                'tg_akhir_pajak'  => 'required|date_format:Y-m-d',
            ]);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get('https://apisakti.bapenda.jabarprov.go.id/api/utilities/info-pajak/cek-bisa-bayar-kedepan', [
                'tg_proses_tetap' => $validated['tg_proses_tetap'],
                'tg_akhir_pajak'  => $validated['tg_akhir_pajak'],
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data dari API',
                    'error' => $response->body()
                ], $response->status());
            }

            return response()->json([
                'success' => true,
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
