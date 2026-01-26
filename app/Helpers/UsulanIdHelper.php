<?php

use Illuminate\Support\Facades\Crypt;

function encryptUsulanId(int $id): string
{
    return urlencode(Crypt::encryptString((string) $id));
}

function decryptUsulanId(string $encryptedId): int
{
    return (int) Crypt::decryptString(urldecode($encryptedId));
}
