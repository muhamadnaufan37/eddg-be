<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, \Closure $next, ...$roles): Response
    {
        // Periksa apakah peran pengguna adalah 1
        if (!in_array($request->user()->role_id, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki izin untuk mengakses resource ini.',
            ], 403);
        }

        return $next($request);
    }
}
