# Changelog

All notable changes to this project will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [0.1.0] — 2026-05-19

Initial release of the package extracted from `aztek/aztek-player` into a standalone Composer library.

### Added

#### Pipeline Commands (`openapi:*`)

- `openapi:publish` — Orchestrates the full 7-step documentation pipeline in sequence. Stops immediately on any step failure.
- `openapi:generate` — Delegates to `scribe:generate` to produce the raw OpenAPI YAML.
- `openapi:add-apidog-status` — Delegates to `scribe:add-apidog-status`.
- `openapi:add-scalar-stability` — Delegates to `scribe:add-scalar-stability`.
- `openapi:inject-environments` — Injects `x-scalar-environments` and publisher ID table into the spec.
- `openapi:inject-jwt-tools` — Deploys the JWT widget asset and injects mount points into `info.description`.
- `openapi:inject-status-badge` — Delegates to `scribe:inject-status-badge`.
- `openapi:export` — Produces a clean export spec (`openapi.export.yaml`) stripped of Scalar-specific fields.

#### Post-processing Commands (`scribe:*`)

- `scribe:add-apidog-status` — Reads `@custom-status` docblock annotations from controllers and writes `x-apidog-status` per operation. Reads `@custom-desc` for custom descriptions.
- `scribe:add-scalar-stability` — Reads `@custom-stability` docblock annotations and writes `x-stability` per operation. Supports `stable`, `experimental`, `deprecated`.
- `scribe:inject-jwt` — Deploys `jwt-generator.js` from `resources/scribe/assets/` to `public/api/` and injects widget mount points into `info.description`. Idempotent.
- `scribe:inject-report` — Injects an API validation report Markdown file into `info.description` with begin/end markers. Idempotent. Supports `--report` and `--yaml` options.
- `scribe:inject-status-badge` — Prepends a `shields.io` badge to each operation's description based on `x-apidog-status`. Idempotent. Supports 9 statuses with distinct colors.

#### Scribe Strategy Plugins

- `NoApiKeyExtractor` — Reads `@noApiKey` docblock tag and sets `no_api_key: true` in `metadata.custom`. Auto-injected into `scribe.strategies.metadata` by the service provider.
- `OptionalHeaderExtractor` — Reads `@custom-headerOptional` tags and returns them as Scribe header entries. Auto-injected into `scribe.strategies.headers`.
- `OptionalHeaderMetaExtractor` — Reads `@custom-headerOptional` tags and stores header names in `metadata.custom.optional_headers` for downstream use by `PerEndpointSecurityGenerator`. Auto-injected into `scribe.strategies.headers`.
- `PerEndpointSecurityGenerator` — Replaces Scribe's global security with per-operation security. `@noApiKey` → `security: []`, default → `[apiKeyScheme]`, `@authenticated` → `[apiKeyScheme, jwtScheme]`. Scheme names configured via `config/openapi.php`.
- `StripApiPrefix` — Strips the `/api` prefix from URIs in non-local environments where an ingress gateway handles it.

#### Service Provider

- `ScribeToolkitServiceProvider` — Registers all 13 Artisan commands and auto-injects `NoApiKeyExtractor`, `OptionalHeaderExtractor`, and `OptionalHeaderMetaExtractor` into the consuming app's Scribe strategies config at boot time.
- Publishes `config/openapi.php` under tag `scribe-toolkit-config`.
- Publishes `resources/assets/` to `resources/scribe/assets/` under tag `scribe-toolkit-assets`.

#### Configuration

- `config/openapi.php` — Feature flags for `jwtTools` widgets (globalApiKeyWidget, jwtTokenGeneratorWidget, jwtValidatorWidget), environment definitions (`x-scalar-environments`), and security scheme name mapping.

#### Developer Tooling

- PHPStan level 8 analysis with Larastan — 0 errors.
- PHPUnit 11 test suite — 109 tests, 209 assertions, ~87% line coverage.
- PCOV as coverage driver (faster than Xdebug, no debug overhead).
- CaptainHook git hooks — `pre-commit` runs PHPStan + PHPUnit; `pre-push` runs full CI. Auto-installed via `composer install`.
- GitHub Actions CI matrix across PHP 8.2/8.3/8.4 × Laravel 11/12.
- Codecov integration for dynamic coverage badge.
- VS Code task buttons for Stan / Test / Coverage / CI / Push.
- `composer ci` script as the single pre-push gate command.

[Unreleased]: https://github.com/sr-architects/laravel-scribe-toolkit/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/sr-architects/laravel-scribe-toolkit/releases/tag/v0.1.0
