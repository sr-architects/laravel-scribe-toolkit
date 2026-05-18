<?php

/**
 * OpenAPI feature flags — single place to enable/disable docs features per project.
 *
 * jwtTools.*  → controls which widgets render in the Scalar docs sidebar
 * apiOverview → controls whether the APIdog Status route table is injected into the spec
 *
 * openapi:inject-jwt-tools reads these flags and auto-updates the CONFIG block in
 * resources/scribe/assets/jwt-generator.js before injecting it into the Scalar docs.
 */
return [
    'features' => [
        'jwtTools' => [
            'globalApiKeyWidget'      => true,  // 🗝️  Global API Key Settings widget
            'jwtTokenGeneratorWidget' => true,  // ⚡  JWT Generator (sign with server secret)
            'jwtValidatorWidget'      => true,  // 🔍  JWT Validator (decode & verify)
        ],
        'jwtKey' => env('JWT_SECRET', ''),
        'apiKey' => env('API_KEY', ''),
        'jwtDefaultPayload' => [],
        'apiOverview'  => true, // 📋  false = skip openapi:add-apidog-status AND openapi:inject-status-badge
        'statusBadges' => true, // 🏷️  false = skip openapi:inject-status-badge (x-apidog-status tag still written)
    ],

    /*
    |--------------------------------------------------------------------------
    | x-scalar-environments
    |--------------------------------------------------------------------------
    |
    | Named environments injected into the OpenAPI spec as x-scalar-environments.
    | Scalar renders these as a global environment switcher in the UI.
    | Each environment's URL is read from an env variable at publish time.
    |
    | Set url to null or '' to omit an environment from the switcher.
    |
    */
    'environments' => [],

    /*
    |--------------------------------------------------------------------------
    | Security scheme names (used by PerEndpointSecurityGenerator)
    |--------------------------------------------------------------------------
    |
    | These must match the keys you define under components.securitySchemes
    | in config/scribe.php.
    |
    */
    'security' => [
        'apiKeyScheme' => env('OPENAPI_API_KEY_SCHEME', 'API_KEY'),
        'jwtScheme'    => env('OPENAPI_JWT_SCHEME', 'JWT_BEARER_TOKEN'),
    ],
];
