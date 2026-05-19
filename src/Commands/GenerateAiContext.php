<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;

class GenerateAiContext extends Command
{
    protected $signature = 'openapi:generate-ai-context
                            {--force : Overwrite files that exist without governance markers}';

    protected $description = 'Generate AI Governance files (CLAUDE.md, .cursorrules) at the project root';

    private const MARKER_START = '<!-- ORG_RULES_START -->';
    private const MARKER_END   = '<!-- ORG_RULES_END -->';

    public function handle(): int
    {
        $appName    = (string) config('app.name', 'Laravel App');
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        $this->syncClaudeMd(base_path('CLAUDE.md'), $appName, $phpVersion);
        $this->syncCursorRules(base_path('.cursorrules'));

        return self::SUCCESS;
    }

    private function syncClaudeMd(string $path, string $appName, string $phpVersion): void
    {
        $block = $this->buildOrgBlock($appName, $phpVersion);

        if (file_exists($path)) {
            $existing = file_get_contents($path);

            if ($existing === false) {
                $this->error("Cannot read {$path}");
                return;
            }

            $startPos = strpos($existing, self::MARKER_START);
            $endPos   = strpos($existing, self::MARKER_END);

            if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
                $before  = substr($existing, 0, $startPos);
                $after   = substr($existing, $endPos + strlen(self::MARKER_END));
                $merged  = $before
                    . self::MARKER_START . "\n\n"
                    . trim($block) . "\n\n"
                    . self::MARKER_END
                    . $after;

                file_put_contents($path, $merged);
                $this->info('✓ CLAUDE.md — org rules block updated (personal notes preserved)');
                return;
            }

            if (! $this->option('force')) {
                $this->warn('CLAUDE.md exists but has no governance markers. Run with --force to overwrite entirely.');
                return;
            }
        }

        file_put_contents($path, $this->buildClaudeFile($appName, $block));
        $this->info("✓ CLAUDE.md written to {$path}");
    }

    private function syncCursorRules(string $path): void
    {
        if (file_exists($path) && ! $this->option('force')) {
            $this->warn('.cursorrules already exists. Run with --force to overwrite.');
            return;
        }

        file_put_contents($path, $this->buildCursorRules());
        $this->info("✓ .cursorrules written to {$path}");
    }

    private function buildClaudeFile(string $appName, string $block): string
    {
        return <<<MD
        # AI Development Context — {$appName}

        > The section between `ORG_RULES_START` and `ORG_RULES_END` is auto-managed by
        > `php artisan openapi:generate-ai-context`. Add project-specific notes **outside**
        > those markers — they will be preserved on every re-run.

        <!-- ORG_RULES_START -->

        {$block}

        <!-- ORG_RULES_END -->
        MD;
    }

    private function buildOrgBlock(string $appName, string $phpVersion): string
    {
        return <<<MD
        ## System Context
        - Project: {$appName}
        - Stack: Laravel 11+ · PHP {$phpVersion}+ · sr-architects/laravel-scribe-toolkit
        - Docs pipeline: `php artisan openapi:publish`
          (7 steps: generate → apidog-status → status-badge → scalar-stability → environments → jwt-tools → export)

        ## Architectural Rules (enforced by this package)

        ### Input — always FormRequest
        Every action that receives user input MUST use a dedicated `FormRequest` class.
        Scribe infers parameters automatically from `rules()` — do NOT add `@bodyParam` docblocks.

        ### Output — always JsonResource
        Every action MUST return a `JsonResource` or `ResourceCollection`.
        Never return raw `response()->json([...])` — Scribe cannot infer types from inline arrays.

        ### Business logic — Service classes only
        Database queries, external HTTP calls, and domain logic belong in Service classes, not controllers.
        Use `Http::fake()` in tests — never make real external HTTP calls.

        ### OpenAPI annotations — minimal
        Required: `@group`, `@authenticated` / `@noApiKey`, `@custom-status`, `@custom-stability`
        Forbidden: `@bodyParam` (use FormRequest), `@response` JSON strings (use factories/transformers)

        ## Commands

        | Command | Purpose |
        |---------|---------|
        | `openapi:publish` | Full 7-step pipeline |
        | `openapi:generate` | Raw spec from Scribe only |
        | `openapi:export` | Clean spec for Apidog / Postman |
        | `openapi:generate-ai-context --force` | Re-generate this file |

        ## Before marking any API task complete
        Run `php artisan openapi:publish` and verify the spec contains the expected operations.
        MD;
    }

    private function buildCursorRules(): string
    {
        return json_encode([
            'instruction' => implode(' ', [
                'You are working in a Laravel project managed by sr-architects/laravel-scribe-toolkit.',
                'Rules: (1) All request input via FormRequest — never inline $request->validate().',
                '(2) All responses via JsonResource/ResourceCollection — never raw response()->json([...]).',
                '(3) Never write @bodyParam or @response docblocks — Scribe infers these automatically.',
                '(4) After modifying any controller, run: php artisan openapi:publish to verify spec integrity.',
            ]),
            'globs' => [
                'app/Http/Controllers/**/*.php',
                'app/Http/Requests/**/*.php',
                'app/Http/Resources/**/*.php',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
