<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FonnteService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('FONNTE_API_KEY');
    }

    public function sendWhatsAppMessage($phone, $message, $countryCode = '62')
    {
        $fullPhone = $countryCode . ltrim($phone, '0'); // Format: 62XXXXXXXXXX

        $response = Http::withHeaders([
            'Authorization' => $this->apiKey
        ])->post('https://api.fonnte.com/send', [
            'target' => $fullPhone,
            'message' => $message,
        ]);

        return $response->json();
    }
}
