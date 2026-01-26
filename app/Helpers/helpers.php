<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Token;

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka)
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}
if (!function_exists('log_bantuan')) {
    function log_bantuan($data)
    {
        $id_user = Auth::check() ? Auth::user()->iduser : null;
        $data_insert = [
            'id_fk' => $data['id_fk'],
            'iduser' => $id_user,
            'tanggal' => now(),
        ];
        \App\Models\BantuanLog::create($data_insert);
    }
}

if (!function_exists('getTokenFonte')) {
    function getTokenFonte()
    {
        $exec = Token::select('token')->where(['source' => 'Fonte' , 'status' => 'active'])->first();
        // get token dari dashboard fonte
        return $exec ? $exec->token : null;
    }
}

if (!function_exists('validate_whatsapp')) {
    function validate_whatsapp($token, $no_tujuan)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/validate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
        'target' => $no_tujuan,
        'countryCode' => '62'
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: '.$token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
if (!function_exists('send_whatsapp')) {
    function send_whatsapp($token, $no_tujuan, $pesan)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $no_tujuan,
            'message' => $pesan
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: '.$token
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}

if (!function_exists('generateKode')) {
   function generateKode($length = 6)
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }
        return $result;
    }
}


