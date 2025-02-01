<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()->currentAccessToken();

        if (!$token) {
            return response()->json(['message' => 'Token tidak ditemukan'], 401);
        }

        // Ambil timestamp kedaluwarsa dari token
        $expiresAt = Carbon::parse($token->created_at)->addHours(8);

        if (Carbon::now()->greaterThan($expiresAt)) {
            $token->delete(); // Hapus token yang sudah expired
            return response()->json(['message' => 'Token expired, silakan login ulang'], 401);
        }

        return $next($request);
    }
}
