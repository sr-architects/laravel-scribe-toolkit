# laravel-scribe-toolkit

A Laravel package that supercharges [Scribe](https://scribe.knuckles.wtf/) with a production-ready 7-step OpenAPI publishing pipeline — complete with lifecycle status badges, multi-environment switching, JWT tools widget, and Scalar stability markers.

Install once. Run one command. Ship beautiful, interactive API docs.

```bash
php artisan openapi:publish
```

> **New to this stack?** See the **[Full Installation Guide](INSTALLATION.md)** for a complete walkthrough — from installing Scribe and Scalar to running your first `openapi:publish`.

---

## Features

- **7-step pipeline** orchestrated by a single Artisan command
- **JWT Tools widget** — a self-contained browser widget for generating and validating JWT tokens directly inside your Scalar docs
- **API lifecycle badges** — per-operation shield.io status badges (`designing` → `released` → `deprecated`)
- **Scalar stability markers** — `x-stability: stable | experimental | deprecated` per operation
- **Multi-environment switcher** — inject named environments into the Scalar UI from your `.env`
- **Clean export spec** — a stripped version of the spec (no Scalar-specific fields) for Apidog, Postman, or any tool
- **Per-endpoint security** — replaces Scribe's global security with precise per-operation security schemes
- **Optional header support** — mark headers as optional via docblock without touching Scribe internals
- **Auto-injected Scribe strategies** — no manual `config/scribe.php` wiring needed for the core plugins

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| Laravel | ^11.0 \| ^12.0 |
| [knuckleswtf/scribe](https://github.com/knuckleswtf/scribe) | ^5.10 |
| symfony/yaml | ^7.0 \| ^8.0 |

---

## Installation

```bash
composer require sr-architects/laravel-scribe-toolkit
```

Laravel's package auto-discovery registers the service provider automatically.

### Publish config

```bash
php artisan vendor:publish --tag=scribe-toolkit-config
```

This creates `config/openapi.php` in your project — the single place for all pipeline feature flags.

### Publish JWT widget asset

```bash
php artisan vendor:publish --tag=scribe-toolkit-assets
```

This copies `jwt-generator.js` to `resources/scribe/assets/`. The widget is a self-contained browser script; no npm build step required.

---

## Configuration

`config/openapi.php`:

```php
return [
    'features' => [
        'jwtTools' => [
            'globalApiKeyWidget'      => true,  // Global API Key widget
            'jwtTokenGeneratorWidget' => true,  // JWT Generator (sign with server secret)
            'jwtValidatorWidget'      => true,  // JWT Validator (decode & verify)
        ],

        // Server secret used by the JWT widget to sign/verify tokens
        'jwtKey' => env('JWT_SECRET', ''),
        'apiKey' => env('API_KEY', ''),

        // Pre-populates the JWT Generator form fields (any payload fields your API needs)
        'jwtDefaultPayload' => [
            'user_id' => env('OPENAPI_DEFAULT_USER_ID', ''),
        ],

        'apiOverview'  => true, // false = skip add-apidog-status + inject-status-badge
        'statusBadges' => true, // false = skip inject-status-badge only
    ],

    // Named environments for the Scalar environment switcher
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
    ],

    // Security scheme names — must match keys in config/scribe.php components.securitySchemes
    'security' => [
        'apiKeyScheme' => env('OPENAPI_API_KEY_SCHEME', 'API_KEY'),
        'jwtScheme'    => env('OPENAPI_JWT_SCHEME', 'JWT_BEARER_TOKEN'),
    ],
];
```

---

## Usage

### Run the full pipeline

```bash
php artisan openapi:publish
```

This runs all 7 steps in sequence and stops immediately if any step fails.

### Run individual steps

```bash
php artisan openapi:generate              # Step 1 — run scribe:generate
php artisan openapi:add-apidog-status     # Step 2 — inject x-apidog-status per operation
php artisan openapi:inject-status-badge   # Step 3 — prepend shield.io badge to descriptions
php artisan openapi:add-scalar-stability  # Step 4 — inject x-stability from @custom-stability
php artisan openapi:inject-environments   # Step 5 — inject x-scalar-environments + publisher table
php artisan openapi:inject-jwt-tools      # Step 6 — deploy JWT widget + inject mount points
php artisan openapi:export                # Step 7 — write clean export spec
```

---

## The Pipeline

```
openapi:generate
    └─▶ openapi:add-apidog-status      (reads @custom-status docblock)
            └─▶ openapi:inject-status-badge   (shield.io badge per operation)
                    └─▶ openapi:add-scalar-stability  (reads @custom-stability docblock)
                            └─▶ openapi:inject-environments  (x-scalar-environments)
                                    └─▶ openapi:inject-jwt-tools  (deploys jwt-generator.js)
                                            └─▶ openapi:export   (clean spec for external tools)
```

**Output files** (inside `storage/app/scribe/` by default):

| File | Purpose |
|---|---|
| `openapi.yaml` | Full spec with Scalar-specific fields, badges, and JWT widget mount points |
| `openapi.export.yaml` | Stripped spec — safe to import into Apidog, Postman, or Stoplight |

---

## Docblock Annotations

Add these tags to your controller method docblocks to control the pipeline output.

### API lifecycle status

```php
/**
 * @custom-status released
 */
public function index() {}
```

| Value | Badge colour |
|---|---|
| `designing` | grey |
| `pending` | orange |
| `developing` | blue |
| `integrating` | purple |
| `testing` | yellow |
| `tested` | green |
| `released` | bright green |
| `deprecated` | red |
| `exception` | red |
| `obsolete` | dark red |

### Scalar stability

```php
/**
 * @custom-stability experimental
 */
public function index() {}
```

Valid values: `stable` · `experimental` · `deprecated`

If no `@custom-stability` annotation is present, `x-stability` is not injected for that operation.

### Custom description

```php
/**
 * @custom-desc Returns a paginated list of players for the given publisher.
 * Supports filtering by status and search by username.
 */
public function index() {}
```

### Skip API key requirement

```php
/**
 * @noApiKey
 */
public function healthCheck() {}
```

Sets `security: []` on this operation — useful for public endpoints.

### Optional headers

```php
/**
 * @custom-headerOptional X-Publisher-Id pub_abc123
 */
public function index() {}
```

The header appears in the spec but is marked `required: false`.

---

## Scribe Strategies

### Auto-injected (no config needed)

These three strategies are automatically added to your Scribe pipeline by the service provider:

| Strategy | Scribe hook | What it does |
|---|---|---|
| `NoApiKeyExtractor` | `metadata` | Reads `@noApiKey` tag |
| `OptionalHeaderExtractor` | `headers` | Reads `@custom-headerOptional` tags |
| `OptionalHeaderMetaExtractor` | `metadata` | Stores optional header names for `PerEndpointSecurityGenerator` |

### Manual registration

These two must be registered in `config/scribe.php` because they depend on your project's security scheme names:

**`PerEndpointSecurityGenerator`** — replaces Scribe's global `security` block with per-operation security. Operations get the API key scheme by default; `@authenticated` operations additionally get the JWT scheme. `@noApiKey` operations get `security: []`.

Scheme names are read from `config/openapi.php` → `security.apiKeyScheme` and `security.jwtScheme`, so they must match the keys you define under `components.securitySchemes` in `config/scribe.php`.

```php
// config/scribe.php
'openapi' => [
    'generators' => [
        \SrArchitects\ScribeToolkit\Strategies\PerEndpointSecurityGenerator::class,
    ],
],
```

```php
// config/openapi.php — set your scheme names to match config/scribe.php
'security' => [
    'apiKeyScheme' => env('OPENAPI_API_KEY_SCHEME', 'API_KEY'),       // matches securitySchemes key
    'jwtScheme'    => env('OPENAPI_JWT_SCHEME', 'JWT_BEARER_TOKEN'),  // matches securitySchemes key
],
```

**`StripApiPrefix`** — removes the `/api` URI prefix from operations in non-local environments (where an ingress or gateway strips it before requests reach the app).

```php
// config/scribe.php
'strategies' => [
    'urlParameters' => [
        ...removeStrategies(Defaults::URL_PARAMETERS_STRATEGIES, [
            Strategies\UrlParameters\GetFromLaravelAPI::class,
        ]),
        \SrArchitects\ScribeToolkit\Strategies\StripApiPrefix::class,
    ],
],
```

---

## JWT Tools Widget

After running `openapi:inject-jwt-tools`, the Scalar docs sidebar gains three interactive tools:

| Widget | Mount point | Purpose |
|---|---|---|
| **Global API Key** | `#global-api-key-mount` | Store and auto-inject the API key into every request |
| **JWT Generator** | `#jwt-generator-mount` | Sign a JWT with the server secret (HS256) |
| **JWT Validator** | `#jwt-validator-mount` | Decode and verify any JWT against the current environment's secret |

The widget reads secrets from the server at request time — they are never exposed in the spec file or the JS source.

Disable individual widgets via `config/openapi.php`:

```php
'jwtTools' => [
    'globalApiKeyWidget'      => true,
    'jwtTokenGeneratorWidget' => true,
    'jwtValidatorWidget'      => false, // hide JWT Validator
],
```

---

## Feature Flags

| Flag | Default | Effect when `false` |
|---|---|---|
| `apiOverview` | `true` | Skips `add-apidog-status` **and** `inject-status-badge` |
| `statusBadges` | `true` | Skips `inject-status-badge` only (status tag still written) |
| `jwtTools.*` | `true` | Hides individual widget panels |

---

## Local Development (path repository)

If you are actively developing this package alongside a Laravel project, use a [path repository](https://getcomposer.org/doc/05-repositories.md#path) for instant symlink-based iteration:

```jsonc
// composer.json of your Laravel project
"repositories": [
    {
        "type": "path",
        "url": "../../sr-architects/laravel-scribe-toolkit",
        "options": { "symlink": true }
    },
    {
        "type": "vcs",
        "url": "git@github.com:sr-architects/laravel-scribe-toolkit.git"
    }
]
```

Composer will use the local symlink when the path exists (local machine) and fall back to the GitHub VCS source when it does not (Docker, CI, staging).

> **Docker note:** If your staging container cannot access the symlink target outside its mount, the VCS fallback handles it automatically — no extra volume mapping needed.

---

## License

MIT
