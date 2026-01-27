<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    public function send(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nohp' => ['required', 'string', 'max:15'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $nohp = $request->nohp;

            // =========================
            // RATE LIMIT (anti-spam)
            // =========================
            $nohpKey = preg_replace('/\D+/', '', $nohp) ?: $nohp;
            $ip = $request->ip() ?? 'unknown';

            $limits = [
                [
                    'key' => "otp:minute:{$nohpKey}",
                    'max' => 1,
                    'decay' => 60,
                    'message' => 'Tunggu 1 menit sebelum meminta OTP kembali',
                ],
                [
                    'key' => "otp:hour:{$nohpKey}",
                    'max' => 5,
                    'decay' => 3600,
                    'message' => 'Terlalu banyak permintaan OTP. Coba lagi dalam 1 jam',
                ],
                [
                    'key' => "otp:day:{$nohpKey}",
                    'max' => 20,
                    'decay' => 86400,
                    'message' => 'Batas harian OTP tercapai. Coba lagi besok',
                ],
                [
                    'key' => "otp:ip:{$ip}",
                    'max' => 10,
                    'decay' => 60,
                    'message' => 'Terlalu banyak permintaan dari IP ini. Coba lagi sebentar',
                ],
            ];

            foreach ($limits as $limit) {
                if (RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                    return response()->json([
                        'code' => 'error',
                        'message' => $limit['message'],
                        'retry_after' => RateLimiter::availableIn($limit['key']),
                    ], 429);
                }
            }
            foreach ($limits as $limit) {
                RateLimiter::hit($limit['key'], $limit['decay']);
            }

            // =========================
            // 🔢 GENERATE OTP
            // =========================
            $otp = rand(100000, 999999);

            // hapus OTP lama (jaga 1 OTP aktif)
            DB::table('otp')->where('no_hp', $nohp)->delete();

            // simpan OTP baru
            DB::table('otp')->insert([
                'no_hp'     => $nohp,
                'kode_otp'  => $otp,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);

            // =========================
            // 📲 VALIDASI & KIRIM WHATSAPP
            // =========================
            $cek = json_decode(
                validate_whatsapp(getTokenFonte(), $nohp)
            );

            if (!$cek || !$cek->status || !empty($cek->not_registered)) {
                return response()->json([
                    'code' => 'error',
                    'message' => 'Nomor WhatsApp tidak valid atau tidak terdaftar'
                ], 422);
            }

            $pesan = "🔐 *Kode OTP Anda*\n\n"
                   . "*{$otp}*\n\n"
                   . "Berlaku selama *5 menit*. "
                   . "Jangan bagikan kode ini kepada siapa pun.";

            $send = json_decode(
                send_whatsapp(getTokenFonte(), $nohp, $pesan)
            );

            if (!$send || !$send->status) {
                return response()->json([
                    'code' => 'error',
                    'message' => 'Gagal mengirim OTP via WhatsApp'
                ], 500);
            }

            return response()->json([
                'code' => 'success',
                'message' => 'OTP berhasil dikirim ke WhatsApp'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'code' => 'error',
                'message' => 'Gagal mengirim OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
