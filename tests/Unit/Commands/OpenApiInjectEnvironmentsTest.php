<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\TestCase;

class OpenApiInjectEnvironmentsTest extends TestCase
{
    // ── Skip logic ────────────────────────────────────────────────────────────

    public function test_skips_when_no_environments_configured(): void
    {
        config()->set('openapi.environments', []);
        $this->writeYaml($this->minimalSpec());

        $this->artisan('openapi:inject-environments')
            ->assertSuccessful()
            ->expectsOutputToContain('Skipped');
    }

    public function test_skips_environments_with_empty_url(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Empty', 'color' => '#fff', 'url' => ''],
        ]);
        $this->writeYaml($this->minimalSpec());

        $this->artisan('openapi:inject-environments')
            ->assertSuccessful()
            ->expectsOutputToContain('Skipped');
    }

    // ── x-scalar-environments injection ───────────────────────────────────────

    public function test_injects_x_scalar_environments(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Production', 'color' => '#10B981', 'url' => 'https://api.example.com'],
            ['name' => 'Staging',    'color' => '#F59E0B', 'url' => 'https://staging.example.com'],
        ]);

        $this->writeYaml($this->minimalSpec());
        $this->artisan('openapi:inject-environments')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertArrayHasKey('x-scalar-environments', $spec);
        $this->assertCount(2, $spec['x-scalar-environments']);
        $this->assertSame('Production', $spec['x-scalar-environments'][0]['name']);
        $this->assertSame('https://api.example.com', $spec['x-scalar-environments'][0]['servers'][0]['url']);
    }

    public function test_filters_out_environments_with_no_url(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Production', 'color' => '#10B981', 'url' => 'https://api.example.com'],
            ['name' => 'Empty',      'color' => '#ccc',    'url' => ''],
        ]);

        $this->writeYaml($this->minimalSpec());
        $this->artisan('openapi:inject-environments')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertCount(1, $spec['x-scalar-environments']);
        $this->assertSame('Production', $spec['x-scalar-environments'][0]['name']);
    }

    public function test_environment_color_defaults_to_indigo_when_missing(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Prod', 'url' => 'https://api.example.com'],
        ]);

        $this->writeYaml($this->minimalSpec());
        $this->artisan('openapi:inject-environments')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('#6366F1', $spec['x-scalar-environments'][0]['color']);
    }

    public function test_trailing_slash_stripped_from_server_url(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Prod', 'color' => '#fff', 'url' => 'https://api.example.com/'],
        ]);

        $this->writeYaml($this->minimalSpec());
        $this->artisan('openapi:inject-environments')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('https://api.example.com', $spec['x-scalar-environments'][0]['servers'][0]['url']);
    }

    // ── Publisher ID table injection ───────────────────────────────────────────

    public function test_no_table_injected_when_no_environment_has_publisher_id(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Prod', 'color' => '#10B981', 'url' => 'https://api.example.com'],
        ]);

        $this->writeYaml($this->minimalSpec('Original description.'));
        $this->artisan('openapi:inject-environments')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('Original description.', $spec['info']['description']);
    }

    public function test_injects_publisher_id_table_into_description(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Prod', 'color' => '#10B981', 'url' => 'https://api.example.com', 'publisherId' => 'pub_prod'],
        ]);

        $this->writeYaml($this->minimalSpec('Existing description.'));
        $this->artisan('openapi:inject-environments')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertStringContainsString('pub_prod', $spec['info']['description']);
        $this->assertStringContainsString('Existing description.', $spec['info']['description']);
    }

    public function test_is_idempotent_does_not_duplicate_table(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Prod', 'color' => '#10B981', 'url' => 'https://api.example.com', 'publisherId' => 'pub_prod'],
        ]);

        $this->writeYaml($this->minimalSpec());
        $this->artisan('openapi:inject-environments')->assertSuccessful();
        $this->artisan('openapi:inject-environments')->assertSuccessful();

        $spec = $this->readYaml();
        // Table should appear exactly once
        $this->assertSame(1, substr_count($spec['info']['description'], 'pub_prod'));
    }

    // ── Error path ────────────────────────────────────────────────────────────

    public function test_fails_when_yaml_not_found(): void
    {
        config()->set('openapi.environments', [
            ['name' => 'Prod', 'color' => '#10B981', 'url' => 'https://api.example.com'],
        ]);

        $this->artisan('openapi:inject-environments')->assertFailed();
    }
}
