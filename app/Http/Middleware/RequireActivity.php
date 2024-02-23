<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check apakah pengguna melakukan aktivitas terakhir dalam 30 menit
        if (auth()->check() && now()->diffInMinutes(auth()->user()->login_terakhir) > config('sanctum.expiration')) {
            auth()->logout(); // Logout pengguna jika tidak aktif
            return response()->json(['message' => 'Unauthorized - Session Expired'], 401);
        }

        return $next($request);
    }
}
