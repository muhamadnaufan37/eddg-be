<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    protected $fonnteService;

    private const ENDPOINTS = [
        'check_device_status' => 'https://api.fonnte.com/device',
    ];

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    public function sendNotification(Request $request)
    {
        // Validasi request
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'nullable|string',
            'template' => 'nullable|string|in:greeting,reminder,custom',
            'variables' => 'nullable|array',
            'country_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors()], 400);
        }

        $phone = $request->phone;
        $countryCode = $request->country_code ?? '62';

        // Daftar template pesan
        $templates = [
            'greeting' => "Halo {name}, selamat datang di layanan kami!",
            'reminder' => "Hai {name}, jangan lupa untuk menyelesaikan pembayaran sebelum {due_date}.",
            'custom' => $request->message ?? "", // Jika tidak ada pesan custom, kosongkan
        ];

        // Pilih template yang diinginkan (default ke custom jika tidak dipilih)
        $templateKey = $request->template ?? 'custom';
        $messageTemplate = $templates[$templateKey] ?? $templates['custom'];

        // Gantilah placeholder di template dengan nilai yang sesuai
        $variables = $request->variables ?? [];
        $message = preg_replace_callback('/{(\w+)}/', function ($matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0]; // Jika tidak ada pengganti, biarkan tetap {name}
        }, $messageTemplate);

        // Kirim pesan menggunakan Fonnte Service
        $response = $this->fonnteService->sendWhatsAppMessage($phone, $message, $countryCode);

        return response()->json(["message" => "Pesan terkirim!", "response" => $response]);
    }

    public function getDeviceStatus(Request $request)
    {
        $phoneNumber = $request->input('phoneNumber'); // Ambil nomor dari request

        $response = Http::withHeaders([
            'Authorization' => config('services.fonnte.account_token'),
        ])->post(self::ENDPOINTS['check_device_status'], [
            'whatsapp' => $phoneNumber,
        ]);

        if ($response->failed()) {
            return response()->json([
                'status' => false,
                'error' => $response->json()['message'] ?? 'Unknown error occurred',
            ], 400);
        }

        return response()->json([
            'status' => true,
            'data' => $response->json(),
        ]);
    }
}
