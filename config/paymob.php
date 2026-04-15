<?php

return [
    'base_url' => env('PAYMOB_BASE_URL', env('BAYMOB_BASE_URL', 'https://accept.paymob.com/api')),
    'api_key' => env('PAYMOB_API_KEY', env('BAYMOB_API_KEY')),
    'integration_id' => env('PAYMOB_INTEGRATION_ID'),
    'iframe_id' => env('PAYMOB_IFRAME_ID'),
    'hmac_secret' => env('PAYMOB_HMAC_SECRET'),

    'currency' => env('PAYMOB_CURRENCY', 'EGP'),
];
