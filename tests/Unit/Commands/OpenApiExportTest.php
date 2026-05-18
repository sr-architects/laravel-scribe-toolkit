<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\TestCase;

class OpenApiExportTest extends TestCase
{
    private const JWT_BEGIN = '<!-- BEGIN: JWT Tools (auto-injected by scribe:inject-jwt) -->';
    private const JWT_END   = '<!-- END: JWT Tools -->';
    private const ENV_BEGIN = '<!-- BEGIN: Environments (auto-injected by openapi:inject-environments) -->';
    private const ENV_END   = '<!-- END: Environments -->';

    // ── Stripping logic ────────────────────────────────────────────────────────

    public function test_removes_x_scalar_environments(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'x-scalar-environments' => [['name' => 'Production', 'servers' => [['url' => 'https://api.test']]]],
        ]);

        $this->artisan('openapi:export')->assertSuccessful();

        $export = $this->readExportYaml();
        $this->assertArrayNotHasKey('x-scalar-environments', $export);
    }

    public function test_strips_jwt_tools_block_from_description(): void
    {
        $desc = "API intro.\n\n"
            . self::JWT_BEGIN . "\n\n## JWT Tools\n\n<div id=\"jwt-generator-mount\"></div>\n\n" . self::JWT_END . "\n";

        $this->writeYaml($this->minimalSpec($desc));

        $this->artisan('openapi:export')->assertSuccessful();

        $export = $this->readExportYaml();
        $this->assertStringNotContainsString('JWT Tools', $export['info']['description']);
        $this->assertStringContainsString('API intro.', $export['info']['description']);
    }

    public function test_strips_environments_block_from_description(): void
    {
        $desc = "API intro.\n\n"
            . self::ENV_BEGIN . "\n\n## Publisher IDs\n\n| Env | ID |\n|---|---|\n\n" . self::ENV_END . "\n";

        $this->writeYaml($this->minimalSpec($desc));

        $this->artisan('openapi:export')->assertSuccessful();

        $export = $this->readExportYaml();
        $this->assertStringNotContainsString('Publisher IDs', $export['info']['description']);
        $this->assertStringContainsString('API intro.', $export['info']['description']);
    }

    public function test_strips_badge_images_from_operation_descriptions(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => [
                '/users' => [
                    'get' => [
                        'description' => "![API Status](https://img.shields.io/badge/status-released-brightgreen)\n\nList users.",
                    ],
                ],
            ],
        ]);

        $this->artisan('openapi:export')->assertSuccessful();

        $export = $this->readExportYaml();
        $desc   = $export['paths']['/users']['get']['description'];

        $this->assertStringNotContainsString('shields.io', $desc);
        $this->assertStringContainsString('List users.', $desc);
    }

    public function test_preserves_regular_content(): void
    {
        $this->writeYaml($this->minimalSpec('Keep this.') + [
            'paths' => [
                '/health' => ['get' => ['description' => 'Health check.', 'operationId' => 'healthCheck']],
            ],
        ]);

        $this->artisan('openapi:export')->assertSuccessful();

        $export = $this->readExportYaml();
        $this->assertSame('Keep this.', $export['info']['description']);
        $this->assertSame('Health check.', $export['paths']['/health']['get']['description']);
    }

    public function test_creates_export_file_next_to_source(): void
    {
        $this->writeYaml($this->minimalSpec());

        $this->artisan('openapi:export')->assertSuccessful();

        $this->assertFileExists($this->exportPath());
    }

    public function test_export_is_valid_yaml(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/test' => ['get' => ['description' => 'Test.']]],
        ]);

        $this->artisan('openapi:export')->assertSuccessful();

        $export = $this->readExportYaml();
        $this->assertIsArray($export);
        $this->assertArrayHasKey('openapi', $export);
    }

    // ── Error path ────────────────────────────────────────────────────────────

    public function test_fails_when_source_yaml_not_found(): void
    {
        $this->artisan('openapi:export')->assertFailed();
    }
}
