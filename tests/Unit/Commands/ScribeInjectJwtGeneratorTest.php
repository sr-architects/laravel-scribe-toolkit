<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\TestCase;

class ScribeInjectJwtGeneratorTest extends TestCase
{
    private string $jsSourceDir;
    private string $jsSourcePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jsSourceDir  = resource_path('scribe/assets');
        $this->jsSourcePath = $this->jsSourceDir . '/jwt-generator.js';

        if (! is_dir($this->jsSourceDir)) {
            mkdir($this->jsSourceDir, 0755, true);
        }
    }

    private function writeStubJs(string $jwtKey = '__JWT_KEY_PLACEHOLDER__', string $apiKey = '__API_KEY_PLACEHOLDER__'): void
    {
        file_put_contents(
            $this->jsSourcePath,
            "(function(){ var key='{$jwtKey}'; var api='{$apiKey}'; })();"
        );
    }

    // ── JS deployment ─────────────────────────────────────────────────────────

    public function test_deploys_js_to_public_api_directory(): void
    {
        $this->writeStubJs();
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-jwt')->assertSuccessful();

        $this->assertFileExists(public_path('api/jwt-generator.js'));
    }

    public function test_replaces_jwt_key_placeholder(): void
    {
        config()->set('openapi.features.jwtKey', 'my-secret');
        $this->writeStubJs();
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-jwt')->assertSuccessful();

        $deployed = file_get_contents(public_path('api/jwt-generator.js'));
        $this->assertStringContainsString('my-secret', $deployed);
        $this->assertStringNotContainsString('__JWT_KEY_PLACEHOLDER__', $deployed);
    }

    public function test_replaces_api_key_placeholder(): void
    {
        config()->set('openapi.features.apiKey', 'my-api-key');
        $this->writeStubJs();
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-jwt')->assertSuccessful();

        $deployed = file_get_contents(public_path('api/jwt-generator.js'));
        $this->assertStringContainsString('my-api-key', $deployed);
        $this->assertStringNotContainsString('__API_KEY_PLACEHOLDER__', $deployed);
    }

    // ── YAML injection ────────────────────────────────────────────────────────

    public function test_injects_jwt_mount_points_into_description(): void
    {
        $this->writeStubJs();
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-jwt')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertStringContainsString('jwt-generator-mount', $spec['info']['description']);
        $this->assertStringContainsString('jwt-validator-mount', $spec['info']['description']);
        $this->assertStringContainsString('global-api-key-mount', $spec['info']['description']);
    }

    public function test_preserves_existing_description(): void
    {
        $this->writeStubJs();
        $this->writeYaml($this->minimalSpec('Existing API description.'));

        $this->artisan('scribe:inject-jwt')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertStringContainsString('Existing API description.', $spec['info']['description']);
        $this->assertStringContainsString('jwt-generator-mount', $spec['info']['description']);
    }

    public function test_is_idempotent_does_not_duplicate_mount_points(): void
    {
        $this->writeStubJs();
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-jwt')->assertSuccessful();
        $this->artisan('scribe:inject-jwt')->assertSuccessful();

        $desc = $this->readYaml()['info']['description'];
        $this->assertSame(1, substr_count($desc, 'jwt-generator-mount'));
    }

    // ── Error paths ───────────────────────────────────────────────────────────

    public function test_fails_when_js_source_not_found(): void
    {
        // No JS file written
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-jwt')->assertFailed();
    }

    public function test_fails_when_yaml_not_found(): void
    {
        $this->writeStubJs();
        // No YAML written

        $this->artisan('scribe:inject-jwt')->assertFailed();
    }

    protected function tearDown(): void
    {
        // Clean up JS files
        if (file_exists($this->jsSourcePath)) {
            unlink($this->jsSourcePath);
        }
        $deployed = public_path('api/jwt-generator.js');
        if (file_exists($deployed)) {
            unlink($deployed);
        }

        parent::tearDown();
    }
}
