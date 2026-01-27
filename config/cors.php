<?php

return [
    'paths' => ['api/*', 'otp/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // ⚠️ untuk testing dulu

    'allowed_headers' => ['*'],

    'supports_credentials' => false,
];
