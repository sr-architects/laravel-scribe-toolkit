<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\TestCase;

class GenerateAiContextTest extends TestCase
{
    private string $claudePath;
    private string $cursorPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->claudePath = base_path('CLAUDE.md');
        $this->cursorPath = base_path('.cursorrules');
    }

    // ── CLAUDE.md — fresh create ──────────────────────────────────────────────

    public function test_creates_claude_md_when_absent(): void
    {
        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $this->assertFileExists($this->claudePath);
        $content = file_get_contents($this->claudePath);
        $this->assertStringContainsString('<!-- ORG_RULES_START -->', $content);
        $this->assertStringContainsString('<!-- ORG_RULES_END -->', $content);
    }

    public function test_claude_md_contains_app_name(): void
    {
        $this->app['config']->set('app.name', 'Acme API');

        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $this->assertStringContainsString('Acme API', file_get_contents($this->claudePath));
    }

    public function test_claude_md_contains_core_architectural_rules(): void
    {
        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $content = file_get_contents($this->claudePath);
        $this->assertStringContainsString('FormRequest', $content);
        $this->assertStringContainsString('JsonResource', $content);
        $this->assertStringContainsString('openapi:publish', $content);
    }

    // ── CLAUDE.md — merge (markers present) ───────────────────────────────────

    public function test_updates_block_between_markers_when_markers_exist(): void
    {
        $initial = implode("\n", [
            '# My Project Notes',
            '',
            'Some personal notes above the block.',
            '',
            '<!-- ORG_RULES_START -->',
            'Old stale content that should be replaced.',
            '<!-- ORG_RULES_END -->',
            '',
            'Personal notes below the block — must survive.',
        ]);
        file_put_contents($this->claudePath, $initial);

        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $content = file_get_contents($this->claudePath);

        $this->assertStringContainsString('My Project Notes', $content, 'Header preserved');
        $this->assertStringContainsString('Some personal notes above the block.', $content, 'Notes above preserved');
        $this->assertStringContainsString('Personal notes below the block', $content, 'Notes below preserved');
        $this->assertStringNotContainsString('Old stale content', $content, 'Stale block replaced');
        $this->assertStringContainsString('FormRequest', $content, 'New rules injected');
    }

    public function test_merge_is_idempotent(): void
    {
        $this->artisan('openapi:generate-ai-context')->assertSuccessful();
        $firstRun = file_get_contents($this->claudePath);

        $this->artisan('openapi:generate-ai-context')->assertSuccessful();
        $secondRun = file_get_contents($this->claudePath);

        $this->assertSame(1, substr_count($secondRun, '<!-- ORG_RULES_START -->'), 'Exactly one start marker');
        $this->assertSame(1, substr_count($secondRun, '<!-- ORG_RULES_END -->'), 'Exactly one end marker');
        $this->assertSame($firstRun, $secondRun, 'Content identical after two runs');
    }

    // ── CLAUDE.md — no markers, no --force ────────────────────────────────────

    public function test_skips_claude_md_without_markers_when_not_forced(): void
    {
        file_put_contents($this->claudePath, "# Manual content — no markers\n");

        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $this->assertSame("# Manual content — no markers\n", file_get_contents($this->claudePath));
    }

    public function test_overwrites_claude_md_without_markers_when_forced(): void
    {
        file_put_contents($this->claudePath, "# Manual content — no markers\n");

        $this->artisan('openapi:generate-ai-context', ['--force' => true])->assertSuccessful();

        $content = file_get_contents($this->claudePath);
        $this->assertStringContainsString('<!-- ORG_RULES_START -->', $content);
    }

    // ── .cursorrules — create / guard / force ─────────────────────────────────

    public function test_creates_cursorrules_when_absent(): void
    {
        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $this->assertFileExists($this->cursorPath);
        $decoded = json_decode(file_get_contents($this->cursorPath), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('instruction', $decoded);
        $this->assertArrayHasKey('globs', $decoded);
    }

    public function test_skips_cursorrules_when_exists_and_not_forced(): void
    {
        file_put_contents($this->cursorPath, '{"custom": "rules"}');

        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $this->assertSame('{"custom": "rules"}', file_get_contents($this->cursorPath));
    }

    public function test_overwrites_cursorrules_when_forced(): void
    {
        file_put_contents($this->cursorPath, '{"custom": "rules"}');

        $this->artisan('openapi:generate-ai-context', ['--force' => true])->assertSuccessful();

        $decoded = json_decode(file_get_contents($this->cursorPath), true);
        $this->assertArrayHasKey('instruction', $decoded);
    }

    public function test_cursorrules_globs_target_controller_and_request_files(): void
    {
        $this->artisan('openapi:generate-ai-context')->assertSuccessful();

        $decoded = json_decode(file_get_contents($this->cursorPath), true);
        $globs = $decoded['globs'];

        $this->assertTrue(
            (bool) array_filter($globs, fn ($g) => str_contains($g, 'Controllers')),
            'Controllers glob present'
        );
        $this->assertTrue(
            (bool) array_filter($globs, fn ($g) => str_contains($g, 'Requests')),
            'Requests glob present'
        );
    }

    // ── Cleanup ───────────────────────────────────────────────────────────────

    protected function tearDown(): void
    {
        foreach ([$this->claudePath, $this->cursorPath] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }
}
