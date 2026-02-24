<?php

// config for Jinom/Keycloak SDK
return [

    /*
    |--------------------------------------------------------------------------
    | Keycloak Server Configuration
    |--------------------------------------------------------------------------
    */
    'base_url' => env('KEYCLOAK_BASE_URL'),
    'realm' => env('KEYCLOAK_REALM'),
    'client_id' => env('KEYCLOAK_CLIENT_ID'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Service Account Configuration (Client Credentials Flow)
    |--------------------------------------------------------------------------
    | Used for service-to-service communication without user context.
    | Can use the same client or a separate service account client.
    */
    'service_account' => [
        'enabled' => env('KEYCLOAK_SERVICE_ACCOUNT_ENABLED', true),
        'client_id' => env('KEYCLOAK_SERVICE_CLIENT_ID', env('KEYCLOAK_CLIENT_ID')),
        'client_secret' => env('KEYCLOAK_SERVICE_CLIENT_SECRET', env('KEYCLOAK_CLIENT_SECRET')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Cache Configuration
    |--------------------------------------------------------------------------
    */
    'token' => [
        'cache_prefix' => env('KEYCLOAK_TOKEN_CACHE_PREFIX', 'keycloak_tokens'),
        'cache_ttl' => env('KEYCLOAK_TOKEN_CACHE_TTL', 60 * 60 * 24 * 30), // 30 days
        'buffer_seconds' => env('KEYCLOAK_TOKEN_BUFFER_SECONDS', 30), // Refresh token 30 seconds before expiry
        'client_token_ttl' => env('KEYCLOAK_CLIENT_TOKEN_TTL', 60 * 5), // 5 minutes for client tokens
    ],

];
