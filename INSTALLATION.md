# Full Installation Guide

Complete walkthrough for setting up a production-ready interactive API documentation system from scratch.

**What you get at the end:**

- `/api/docs` — Scalar UI with live "Try it out", JWT widget, environment switcher, and status badges
- `/api/spec/openapi.yaml` — Full spec for Scalar
- `/api/spec/openapi.export.yaml` — Clean spec ready for import into Apidog / Postman / Stoplight
- `php artisan openapi:publish` — one command to regenerate everything

---

## Stack

| Package | Role |
|---|---|
| [`knuckleswtf/scribe`](https://scribe.knuckles.wtf/) | Scans routes and generates the OpenAPI spec |
| [`scalar/laravel`](https://github.com/scalar/scalar) | Renders the interactive Scalar UI |
| `sr-architects/laravel-scribe-toolkit` | Pipeline, JWT widget, status badges, environments |

---

## Step 1 — Install Scribe

```bash
composer require knuckleswtf/scribe
php artisan vendor:publish --provider="Knuckles\Scribe\ScribeServiceProvider" --tag=scribe-config
```

Replace `config/scribe.php` with the following minimal production-ready config:

```php
<?php

use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Extracting\Strategies;

return [
    // Document all routes under api/*
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/*'],
                'domains'  => ['*'],
            ],
            'include' => [],
            'exclude' => [],
        ],
    ],

    // Write spec to storage/app/scribe/ (accessed via custom routes below)
    'type' => 'static',
    'static' => [
        'output_path' => 'storage/app/scribe',
    ],

    // Disable Scribe's global auth — PerEndpointSecurityGenerator handles it per operation
    'auth' => [
        'enabled'  => false,
        'default'  => false,
        'in'       => 'bearer',
        'name'     => 'Authorization',
        'use_value' => env('APP_ENV') === 'production' ? null : env('EXAMPLE_JWT_TOKEN'),
        'placeholder' => 'YOUR_JWT_TOKEN',
        'extra_info' => '',
    ],

    'postman' => ['enabled' => false],

    'openapi' => [
        'enabled' => true,
        'version' => '3.1.0',
        'overrides' => [
            'components' => [
                'securitySchemes' => [
                    // Bearer JWT — used by @authenticated endpoints
                    'JWT_BEARER_TOKEN' => [
                        'type'        => 'http',
                        'scheme'      => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                    // API Key header — applied to all endpoints by default
                    'YOUR_API_KEY' => [
                        'type' => 'apiKey',
                        'in'   => 'header',
                        'name' => 'X-Api-Key',
                    ],
                ],
            ],
        ],
        // Per-endpoint security (replaces Scribe's global security block)
        'generators' => [
            \SrArchitects\ScribeToolkit\Strategies\PerEndpointSecurityGenerator::class,
        ],
    ],

    'groups' => [
        'default' => 'Endpoints',
        'order'   => [],
    ],

    'logo'         => false,
    'last_updated' => 'Last updated: {date:F j, Y}',

    'examples' => [
        'faker_seed'    => 1234,
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],

    'strategies' => [
        'metadata' => [
            ...Defaults::METADATA_STRATEGIES,
            // NoApiKeyExtractor + OptionalHeaderMetaExtractor
            // are auto-injected by ScribeToolkitServiceProvider
        ],
        'headers' => [
            ...Defaults::HEADERS_STRATEGIES,
            // OptionalHeaderExtractor auto-injected by ScribeToolkitServiceProvider
            Strategies\StaticData::withSettings(data: [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ]),
        ],
        'urlParameters' => [
            // Remove the default Laravel URL param extractor if your gateway
            // strips the /api prefix before requests reach the app
            ...removeStrategies(Defaults::URL_PARAMETERS_STRATEGIES, [
                Strategies\UrlParameters\GetFromLaravelAPI::class,
            ]),
            \SrArchitects\ScribeToolkit\Strategies\StripApiPrefix::class,
        ],
        'queryParameters' => [
            ...Defaults::QUERY_PARAMETERS_STRATEGIES,
        ],
        'bodyParameters' => [
            Strategies\BodyParameters\GetFromFormRequest::class,
            Strategies\BodyParameters\GetFromBodyParamAttribute::class,
            Strategies\BodyParameters\GetFromBodyParamTag::class,
        ],
        'responses' => [
            ...Defaults::RESPONSES_STRATEGIES,
        ],
        'rbac' => [
            ...Defaults::RBAC_STRATEGIES,
        ],
    ],
];
```

> **Security scheme names** (`JWT_BEARER_TOKEN`, `YOUR_API_KEY`) must match what `PerEndpointSecurityGenerator` emits and what you configure in Scalar below. Rename them to match your project.

---

## Step 2 — Install Scalar

```bash
composer require scalar/laravel
php artisan vendor:publish --provider="Scalar\ScalarLaravel\ScalarServiceProvider"
```

This creates `config/scalar.php`. Update the key fields:

```php
return [
    'path' => '/api/docs',             // URL where the Scalar UI is served
    'url'  => '/api/spec/openapi.yaml', // URL of the spec file (added in Step 4)

    'configuration' => [
        'theme'              => 'default', // alternate | bluePlanet | deepSpace | moon | saturn | …
        'layout'             => 'modern',
        'forceDarkModeState' => 'dark',
        'showSidebar'        => true,
        'defaultHttpClient'  => [
            'targetId'  => 'shell',
            'clientKey' => 'curl',
        ],
        'metaData' => [
            'title' => config('app.name') . ' API Reference',
        ],

        // Pre-fill auth values in non-production environments
        'authentication' => [
            'preferredSecurityScheme' => 'JWT_BEARER_TOKEN',
            'securitySchemes' => [
                'JWT_BEARER_TOKEN' => [
                    'token' => env('APP_ENV') === 'production' ? null : env('EXAMPLE_JWT_TOKEN', ''),
                ],
                'YOUR_API_KEY' => [
                    'value' => env('APP_ENV') === 'production' ? null : env('YOUR_API_KEY', ''),
                ],
            ],
        ],
    ],
];
```

---

## Step 3 — Install laravel-scribe-toolkit

```bash
composer require sr-architects/laravel-scribe-toolkit
```

Publish the config and the JWT widget asset:

```bash
php artisan vendor:publish --tag=scribe-toolkit-config
php artisan vendor:publish --tag=scribe-toolkit-assets
```

Update `config/openapi.php` for your project:

```php
return [
    'features' => [
        'jwtTools' => [
            'globalApiKeyWidget'      => true,
            'jwtTokenGeneratorWidget' => true,
            'jwtValidatorWidget'      => true,
        ],

        // Server secret used by the JWT widget to sign/verify tokens
        'jwtKey' => env('JWT_SECRET', ''),
        'apiKey' => env('API_KEY', ''),

        // Pre-populates the JWT Generator form (any payload fields your API needs)
        'jwtDefaultPayload' => [
            'user_id' => env('OPENAPI_DEFAULT_USER_ID', ''),
        ],

        'apiOverview'  => true,
        'statusBadges' => true,
    ],

    'environments' => [
        // Add your named environments here
    ],

    // Security scheme names — must match keys in config/scribe.php components.securitySchemes
    'security' => [
        'apiKeyScheme' => env('OPENAPI_API_KEY_SCHEME', 'API_KEY'),
        'jwtScheme'    => env('OPENAPI_JWT_SCHEME', 'JWT_BEARER_TOKEN'),
    ],
];
```

> **Customising scheme names:** If your `config/scribe.php` uses different keys for `securitySchemes` (e.g. `MY_API_KEY` instead of `API_KEY`), set `OPENAPI_API_KEY_SCHEME=MY_API_KEY` in `.env` or change the default directly in `config/openapi.php`. The same applies to `jwtScheme`.

---

## Step 3b — Environments (optional)

Update the `environments` array in `config/openapi.php` with your project's named environments:

```php
    'environments' => [
        [
            'name'  => 'Production',
            'color' => '#10B981',
            'url'   => env('OPENAPI_PROD_BASE_URL', ''),
        ],
        [
            'name'  => 'Staging',
            'color' => '#F59E0B',
            'url'   => env('OPENAPI_STAGING_BASE_URL', ''),
        ],
        [
            'name'  => 'Local',
            'color' => '#6366F1',
            'url'   => env('APP_URL', 'http://localhost'),
        ],
    ],
];
```

---

## Step 4 — Add spec routes

Add these two routes to `routes/web.php` so Scalar and external tools can fetch the spec files:

```php
Route::get('/api/spec/openapi.yaml', function () {
    $path = storage_path('app/scribe/openapi.yaml');
    abort_if(! file_exists($path), 404);
    return response()->file($path, ['Content-Type' => 'application/yaml']);
});

Route::get('/api/spec/openapi.export.yaml', function () {
    $path = storage_path('app/scribe/openapi.export.yaml');
    abort_if(! file_exists($path), 404);
    return response()->file($path, ['Content-Type' => 'application/yaml']);
});
```

---

## Step 5 — Environment variables

Add to `.env`:

```env
# JWT widget secrets (read by config/openapi.php)
JWT_SECRET=your-server-signing-secret
API_KEY=your-api-key

# Pre-fill auth in Scalar UI (non-production only)
EXAMPLE_JWT_TOKEN=eyJ...

# Environment switcher URLs
OPENAPI_PROD_BASE_URL=https://api.example.com
OPENAPI_STAGING_BASE_URL=https://staging-api.example.com

# JWT Generator default payload (optional)
OPENAPI_DEFAULT_USER_ID=

# Security scheme names — only set if you renamed them in config/scribe.php
# OPENAPI_API_KEY_SCHEME=API_KEY
# OPENAPI_JWT_SCHEME=JWT_BEARER_TOKEN
```

---

## Step 6 — Generate docs

```bash
php artisan openapi:publish
```

Done. Visit `/api/docs`.

---

## What each step produces

```
php artisan openapi:publish
│
├─ openapi:generate              → storage/app/scribe/openapi.yaml (raw Scribe output)
├─ openapi:add-apidog-status     → adds x-apidog-status to every operation
├─ openapi:inject-status-badge   → prepends shield.io badge to every description
├─ openapi:add-scalar-stability  → adds x-stability where @custom-stability is set
├─ openapi:inject-environments   → adds x-scalar-environments + Publisher ID table
├─ openapi:inject-jwt-tools      → deploys jwt-generator.js, injects widget mount points
└─ openapi:export                → storage/app/scribe/openapi.export.yaml (clean, no Scalar fields)
```

---

## Add to CI / deployment

If you regenerate docs on deploy, add to your deployment script:

```bash
php artisan openapi:publish
```

Or wire it into `composer.json` as a post-install hook if you prefer:

```json
"scripts": {
    "post-update-cmd": [
        "@php artisan openapi:publish --no-interaction"
    ]
}
```

---

## Troubleshooting

**`openapi.yaml not found`** — Run `php artisan openapi:generate` first, or run the full `openapi:publish`.

**`jwt-generator.js not found in resources/scribe/assets/`** — Run `php artisan vendor:publish --tag=scribe-toolkit-assets`.

**Scalar shows an empty page** — Check that `/api/spec/openapi.yaml` returns a valid YAML response (visit the URL directly in your browser).

**Symlink error in Docker/staging** — See the [Local Development](README.md#local-development-path-repository) section in the README. The VCS fallback handles staging automatically.

**`PerEndpointSecurityGenerator` not applying security** — Ensure it is listed under `openapi.generators` in `config/scribe.php`, not under `strategies`.
