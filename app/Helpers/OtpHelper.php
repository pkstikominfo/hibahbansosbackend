<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

function validateOtp(string $nohp, string $otp): bool
{
    $expiredAt = Carbon::now()->subMinutes(5);

    $record = DB::table('otp')
        ->where('no_hp', $nohp)
        ->where('kode_otp', $otp)
        ->where('created_at', '>=', $expiredAt)
        ->first();

    if (!$record) {
        return false;
    }

    // hapus OTP setelah dipakai (one time use)
    DB::table('otp')->where('id', $record->id)->delete();

    return true;
}
