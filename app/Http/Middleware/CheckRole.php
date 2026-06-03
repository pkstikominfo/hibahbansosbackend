<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Ambil auth_user yang sudah di-sync oleh CheckSSO
        $authUser = $request->auth_user ?? auth()->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Cek apakah user punya salah satu role yang dibutuhkan berdasarkan field peran di DB lokal
        // Field peran ini sudah di-sync dengan Keycloak roles di CheckSSO
        $hasRole = in_array($authUser->peran, $roles);

        if (!$hasRole) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Required role: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}