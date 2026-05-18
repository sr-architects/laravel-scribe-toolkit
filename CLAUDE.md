# CLAUDE.md — sr-architects/laravel-scribe-toolkit

> Read this file before starting any task.

---

## Package Identity

`sr-architects/laravel-scribe-toolkit` is a **shared infrastructure package** that governs the OpenAPI documentation pipeline for Laravel applications via Artisan commands and Scribe strategy plugins.

This package is an **upstream dependency** of production applications. A breaking change here cascades downstream. Treat every PR as a production change.

---

## Quality Gates

All three gates must pass before every push. No exceptions.

```bash
composer ci   # PHPStan level 8 → PHPUnit (run this before pushing)
```

| Gate | Tool | Passing Criteria |
|---|---|---|
| Static Analysis | PHPStan level 8 | 0 errors |
| Test Suite | PHPUnit 11 | 0 failures, 0 warnings |
| Line Coverage | PCOV | ≥ 80% |

Git hooks enforce this automatically via CaptainHook (`pre-commit`, `pre-push`).

---

## Coverage Policy

| Threshold | Coverage | Meaning |
|---|---|---|
| Hard floor | ≥ 80% | Minimum acceptable. PRs below this are rejected. |
| Target | ≥ 90% | Goal for core infrastructure. Push toward this. |
| Ceiling | Not enforced at 100% | See rationale below. |

**Why not 100%?**
The marginal cost of each additional percent of coverage follows an exponential curve in the final 10%. Code in that range is typically error paths, platform-specific branches, or third-party adapter boundaries where unit tests provide negligible confidence signal relative to the maintenance burden they impose. Per Google's Site Reliability Engineering practices and the Law of Diminishing Returns, engineering time saved in the 90–100% range is better invested in integration tests, documentation, or features.

**Do not chase 100% by testing:**
- Trivial getters/setters with no logic
- Dead code paths managed entirely by the framework
- External API boundaries that require complex mocking to yield low-signal tests

---

## Architecture

```
src/
├── Commands/
│   ├── OpenApi*       Pipeline commands  (openapi:publish, generate, export, …)
│   └── Scribe*        Post-processing    (scribe:add-*, scribe:inject-*)
├── Strategies/        Scribe extraction strategy plugins
│   ├── NoApiKeyExtractor.php
│   ├── OptionalHeaderExtractor.php
│   ├── OptionalHeaderMetaExtractor.php
│   ├── PerEndpointSecurityGenerator.php
│   └── StripApiPrefix.php
└── ScribeToolkitServiceProvider.php
```

**Invariants — never break these:**

- `ScribeToolkitServiceProvider` must auto-inject three strategies (`NoApiKeyExtractor`, `OptionalHeaderExtractor`, `OptionalHeaderMetaExtractor`) into the consuming app's Scribe config at boot time.
- Commands that delegate to other commands must use the `protected runStep()` hook. Never call `Artisan::call()` directly — it prevents testing without mocking the final Testbench kernel.
- Every strategy `__invoke()` must carry `@param array<mixed>` and `@return array<string, mixed>|null` docblocks.

---

## Testing

```
tests/
├── Feature/
│   └── ServiceProviderTest.php    # Command registration, config merging, strategy injection
└── Unit/
    ├── Commands/                  # One file per command
    └── Strategies/                # One file per strategy
```

### Command test pattern

Testbench v10 marks its Kernel `final`, so `Artisan::shouldReceive()` is unavailable. Use the **stub subclass pattern** instead:

```php
class StubMyCommand extends MyCommand
{
    public array $calls = [];
    public array $returnCodes = [];

    protected function runStep(string $command): int
    {
        $this->calls[] = $command;
        return $this->returnCodes[$command] ?? 0;
    }
}
```

Commands that operate directly on YAML files can use `$this->artisan()` via Testbench normally.

### TestCase helpers

```php
$this->writeYaml(array $spec)                        // write openapi.yaml to temp path
$this->readYaml(): array                             // read it back after the command runs
$this->minimalSpec(string $description = ''): array  // minimal valid spec skeleton
```

### Strategy instantiation

Always use `$this->app->make(StrategyClass::class)` — never `new StrategyClass()`. The constructor requires a `DocumentationConfig` instance resolved from the container.

---

## PHPStan Rules

Level 8, enforced. Fix the underlying cause; never suppress with `@phpstan-ignore`.

| Pattern | Correct fix |
|---|---|
| `preg_replace()` returns `string\|null` | Append `?? $original` |
| `file_get_contents()` returns `string\|false` | Check `=== false` before use |
| `$this->option()` returns `mixed` | Use `is_string()` guard, not a cast |
| `Route::getRoutes()` returns `RouteCollectionInterface` | Call `.getRoutes()` to get an iterable |
| Override narrows `$groupedEndpoints` vs parent shape tuple | Drop the `@param` annotation for that parameter; inherit the parent's type |

---

## CHANGELOG Protocol

Every PR with a user-visible change **must** update `CHANGELOG.md` before merge.

Add entries under `## [Unreleased]`. Never edit a released version block.

```markdown
## [Unreleased]

### Added
- New commands, strategies, config keys, or public API surface.

### Changed
- Behavioral changes that remain backward-compatible.

### Fixed
- Bug fixes. State the root cause, not just the symptom.

### Removed
- Anything deleted from the public API.

### Deprecated
- Anything scheduled for removal in the next major version.

### Security
- Vulnerability fixes. Include CVE identifier if applicable.
```

**Must document:** new/removed commands, strategy behavior changes, config key additions/removals, bug fixes affecting output, any breaking change (prefix with `[BREAKING]`).

**Skip:** internal refactors with no behavior change, typo fixes in comments, dev-dependency bumps, adding tests only.

**On release:**
1. Rename `## [Unreleased]` → `## [x.y.z] — YYYY-MM-DD`
2. Add a fresh empty `## [Unreleased]` at the top
3. Update the comparison links at the bottom of the file
4. Tag: `git tag -a vx.y.z -m "Release x.y.z"`

---

## Commit Rules

- No `Co-Authored-By: Claude` or any AI attribution in any commit, ever.
- Commit messages use imperative mood, English (e.g. `Fix`, `Add`, `Remove`).
- Bug fix commits must state the root cause in the body, not just the symptom.

---

## CI / CD

GitHub Actions runs on every push to `main` and every pull request.

Matrix: PHP 8.2 / 8.3 / 8.4 × Laravel 11 / 12 (4 combinations).

Coverage is collected only on the PHP 8.4 / Laravel 12 job to avoid duplicate Codecov reports. All other jobs run tests without a coverage driver for speed.

`composer install` in CI uses `--no-scripts` to skip CaptainHook hook installation (irrelevant in a non-interactive environment).

Required secret: `CODECOV_TOKEN` — set in GitHub repository settings.

---

## Commands Reference

```bash
composer analyse          # PHPStan level 8
composer test             # PHPUnit, no coverage (fast)
composer test:coverage    # PHPUnit + PCOV → coverage/html/ + stdout summary
composer test:ci          # PHPUnit + PCOV → coverage/clover.xml (used by CI)
composer ci               # analyse + test  ← run this before every push
composer hooks:install    # (Re-)install CaptainHook git hooks
```

Coverage driver: **PCOV**
Install: `brew install shivammathur/extensions/pcov@8.4`
