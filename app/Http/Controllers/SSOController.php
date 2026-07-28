<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User; // 🔥 TAMBAHAN WAJIB: Import Model User

class SSOController extends Controller
{
    // 1. Kembalikan URL login Keycloak ke frontend
    public function getLoginUrl(Request $request)
    {
        $request->validate([
            'redirect_uri' => 'required|url',
            'state'        => 'required|string',
        ]);
        
        \Log::info('Redirect URI yang diterima dari Frontend: ' . $request->redirect_uri);
        \Log::info('Redirect URI dari Config/Env: ' . config('services.bds.redirect_uri'));

        $params = http_build_query([
            'client_id'     => config('services.bds.client_id'),
            'redirect_uri'  => $request->redirect_uri,
            'response_type' => 'code',
            'scope'         => 'openid profile email',
            'state'         => $request->state,
        ]);

        return response()->json([
            'url' => config('services.bds.base_url') . '/auth?' . $params,
        ]);
    }

    // 2. Tukar code dengan token, kembalikan ke frontend
    public function exchangeCode(Request $request)
    {
        $request->validate([
            'code'         => 'required|string',
            'redirect_uri' => 'required|url',
        ]);

        try {
            // ✅ BYPASS SSL DITAMBAHKAN DI SINI
            $response = Http::withoutVerifying()->asForm()->post(config('services.bds.base_url') . '/token', [
                'grant_type'    => 'authorization_code',
                'client_id'     => config('services.bds.client_id'),
                'client_secret' => config('services.bds.client_secret'),
                'redirect_uri'  => $request->redirect_uri,
                'code'          => $request->code,
            ]);

            //if ($response->failed()) {
             //   return response()->json([
             //       'message' => 'Gagal menukar token dengan Keycloak',
               //     'detail'  => $response->json(),
                //], 400);
            //}
            if ($response->failed()) {
                // 👇 UBAH BAGIAN INI AGAR DETAIL ERRORNYA TERCETAK JELAS 👇
                \Log::error('Keycloak Token Error Response:', $response->json());

                return response()->json([
                    'message' => 'Gagal menukar token dengan Keycloak',
                    'detail'  => $response->json(),
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal terhubung ke server SSO Keycloak. Pastikan URL Keycloak dapat diakses dari backend.',
                'error'   => $e->getMessage()
            ], 500);
        }

        $data = $response->json();
        $accessToken = $data['access_token'];

        // ====================================================================
        // 🔥 TAMBAHAN WAJIB: Sinkronisasi User ke Database Lokal
        // ====================================================================
        // ✅ BYPASS SSL DITAMBAHKAN DI SINI JUGA
        $userInfoResponse = Http::withoutVerifying()->withToken($accessToken)
                                ->get(config('services.bds.base_url') . '/userinfo');

        if ($userInfoResponse->successful()) {
            $ssoUser = $userInfoResponse->json();
            
            // Catat ke DB Lokal. 
            // Pastikan tabel users kamu memiliki kolom 'sso_sub' (atau ubah nama kolomnya sesuai database kamu)
            User::updateOrCreate(
                ['sso_sub' => $ssoUser['sub']], // 'sub' adalah UUID unik dari Keycloak
                [
                    'name'     => $ssoUser['name'] ?? $ssoUser['preferred_username'],
                    'email'    => $ssoUser['email'] ?? null,
                    'username' => $ssoUser['preferred_username'],
                    // Jika ada default kolom lain (misal role), bisa ditambahkan di sini:
                    // 'role' => 'user' 
                ]
            );
        }
        // ====================================================================

        return response()->json([
            'access_token'  => $accessToken,
            'refresh_token' => $data['refresh_token'],
            'expires_in'    => $data['expires_in'],
        ]);
    }

    // 3. Refresh access token
    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // ✅ BYPASS SSL DITAMBAHKAN DI SINI
        $response = Http::withoutVerifying()->asForm()->post(config('services.bds.base_url') . '/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => config('services.bds.client_id'),
            'client_secret' => config('services.bds.client_secret'),
            'refresh_token' => $request->refresh_token,
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Refresh token expired, silakan login ulang'], 401);
        }

        $data = $response->json();

        return response()->json([
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in'    => $data['expires_in'],
        ]);
    }

    // 4. Logout — invalidate token di Keycloak
    public function logout(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // ✅ BYPASS SSL DITAMBAHKAN DI SINI
        Http::withoutVerifying()->asForm()->post(config('services.bds.base_url') . '/logout', [
            'client_id'     => config('services.bds.client_id'),
            'client_secret' => config('services.bds.client_secret'),
            'refresh_token' => $request->refresh_token,
        ]);

        return response()->json(['message' => 'Logout berhasil']);
    }

    // 5. Backchannel logout (dipanggil Keycloak)
    public function backchannelLogout(Request $request)
    {
        $logoutToken = $request->input('logout_token');
        $payload = json_decode(base64_decode(explode('.', $logoutToken)[1]));

        abort_unless(
            isset($payload->events->{'http://schemas.openid.net/event/backchannel-logout'}),
            400
        );

        // Di sini kamu bisa invalidate token di DB jika kamu menyimpannya
        // Contoh: \DB::table('active_tokens')->where('sso_sid', $payload->sid)->delete();

        return response()->noContent(); // 204
    }

    // 6. Profil User (dari DB Lokal yang sudah di-sync)
    // public function me(Request $request)
    // {
    //     $user = $request->auth_user ?? auth()->user();
    //     if ($user) {
    //         $user->load('opd'); // Load relasi OPD jika ada
    //     }
        
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Data profil berhasil diambil',
    //         'data'    => $user
    //     ]);
    // }
    // 6. Profil User (dari DB Lokal yang sudah di-sync)
    public function me(Request $request)
    {
        try {
            $user = $request->auth_user ?? auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan atau belum terautentikasi'
                ], 401);
            }

            // Aman dari crash jika relasi opd tidak ada
            if (method_exists($user, 'opd')) {
                $user->loadMissing('opd');
            }

            return response()->json([
                'success' => true,
                'message' => 'Data profil berhasil diambil',
                'data'    => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server saat mengambil profil',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 7. Data UserInfo dari Keycloak
    public function userinfo(Request $request)
    {
        $token = $request->bearerToken();
        
        // ✅ BYPASS SSL DITAMBAHKAN DI SINI
        $response = Http::withoutVerifying()->withToken($token)->get(config('services.bds.base_url') . '/userinfo');
        
        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data userinfo dari Keycloak',
                'detail'  => $response->json(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data userinfo berhasil diambil',
            'data'    => $response->json()
        ]);
    }
}