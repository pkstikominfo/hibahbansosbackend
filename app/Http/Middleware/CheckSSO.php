<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

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
            $jwks = \Illuminate\Support\Facades\Cache::remember('bds_jwks', 60 * 60 * 6, function () {
                $raw = file_get_contents(env('BDS_SSO_BASE') . '/certs');
                if (!$raw) {
                    throw new \Exception('Gagal fetch JWKS dari Keycloak');
                }
                return json_decode($raw, true);
            });

            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));

            $request->merge(['sso_user' => (array) $decoded]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token invalid atau expired'], 401);
        }

        return $next($request);
    }
}
