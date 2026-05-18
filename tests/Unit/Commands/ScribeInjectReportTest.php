<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\TestCase;

class ScribeInjectReportTest extends TestCase
{
    private string $reportPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportPath = base_path('api_validation_report.md');
    }

    private function writeReport(string $content = "# Validation Report\n\nAll good."): void
    {
        file_put_contents($this->reportPath, $content);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_injects_report_into_description(): void
    {
        $this->writeReport("# My Report\n\nAll checks passed.");
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-report')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertStringContainsString('My Report', $spec['info']['description']);
        $this->assertStringContainsString('All checks passed.', $spec['info']['description']);
    }

    public function test_preserves_existing_description(): void
    {
        $this->writeReport("# Report");
        $this->writeYaml($this->minimalSpec('Original API description.'));

        $this->artisan('scribe:inject-report')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertStringContainsString('Original API description.', $spec['info']['description']);
        $this->assertStringContainsString('Report', $spec['info']['description']);
    }

    public function test_is_idempotent_does_not_duplicate_report(): void
    {
        $this->writeReport("# Report\n\nContent.");
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-report')->assertSuccessful();
        $this->artisan('scribe:inject-report')->assertSuccessful();

        $desc = $this->readYaml()['info']['description'];
        $this->assertSame(1, substr_count($desc, '<!-- BEGIN: API Validation Report'));
    }

    public function test_accepts_custom_report_path_option(): void
    {
        $customPath = base_path('custom_report.md');
        file_put_contents($customPath, "# Custom Report\n\nDone.");

        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-report', ['--report' => 'custom_report.md'])->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertStringContainsString('Custom Report', $spec['info']['description']);

        unlink($customPath);
    }

    // ── Error paths ───────────────────────────────────────────────────────────

    public function test_fails_when_report_file_not_found(): void
    {
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-report')->assertFailed();
    }

    public function test_fails_when_yaml_not_found(): void
    {
        $this->writeReport();

        $this->artisan('scribe:inject-report')->assertFailed();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->reportPath)) {
            unlink($this->reportPath);
        }

        parent::tearDown();
    }
}
