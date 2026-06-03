<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CheckSSO
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            // Cache JWKS selama 6 jam — public key Keycloak jarang berubah
            $jwks = Cache::remember('bds_jwks', 60 * 60 * 6, function () {
                $baseUrl = config('services.bds.base_url');
                $raw = file_get_contents($baseUrl . '/certs');
                if (!$raw) {
                    throw new \Exception('Gagal fetch JWKS dari Keycloak');
                }
                return json_decode($raw, true);
            });

            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));
            $ssoUserPayload = json_decode(json_encode($decoded), true);

            // Sync user ke DB lokal
            $authUser = User::syncFromSsoToken($ssoUserPayload);
            if (!$authUser) {
                return response()->json(['message' => 'Gagal sinkronisasi data user'], 500);
            }

            $request->merge([
                'sso_user' => $ssoUserPayload,
                'auth_user' => $authUser
            ]);

            // Set user secara stateless agar helper auth()->user() bisa dipakai
            auth()->setUser($authUser);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Token invalid atau expired', 'error' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
