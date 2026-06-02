<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Ambil data user dari token SSO (diinject oleh middleware CheckSSO)
        $ssoUser = $request->sso_user;

        if (!$ssoUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Ambil roles dari token Keycloak
        $userRoles = $ssoUser['realm_access']['roles'] ?? [];

        // Cek apakah user punya salah satu role yang dibutuhkan
        $hasRole = collect($userRoles)->contains(fn($role) => in_array($role, $roles));

        if (!$hasRole) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Required role: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}