<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limits
    |--------------------------------------------------------------------------
    |
    | Configure per-minute rate limits for API calls and authentication attempts.
    | Set the environment variables to tune these limits in different
    | environments without changing code.
    |
    */
    'rate_limit' => env('API_RATE_LIMIT', 120),
    'auth_rate_limit' => env('API_AUTH_RATE_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | API Token Expiration
    |--------------------------------------------------------------------------
    |
    | Tokens expire after the configured number of minutes. Set to null or zero
    | to create non-expiring tokens (not recommended for production).
    |
    */
    'token_expiration_minutes' => env('API_TOKEN_EXPIRATION', 43200), // 30 days
];
