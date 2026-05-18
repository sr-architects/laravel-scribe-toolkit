<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\Fixtures\TestController;
use SrArchitects\ScribeToolkit\Tests\TestCase;

class ScribeAddApiDogStatusTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);
        $router->get('api/deprecated',      [TestController::class, 'withDeprecatedStatus']);
        $router->get('api/invalid-status',  [TestController::class, 'withInvalidStatus']);
    }

    // ── @custom-status annotation ─────────────────────────────────────────────

    public function test_injects_status_from_docblock_annotation(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/released' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-apidog-status')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('released', $spec['paths']['/released']['get']['x-apidog-status']);
    }

    public function test_defaults_to_pending_when_no_annotation(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/plain' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-apidog-status')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('pending', $spec['paths']['/plain']['get']['x-apidog-status']);
    }

    public function test_defaults_to_pending_for_invalid_status_value(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/invalid-status' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-apidog-status')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('pending', $spec['paths']['/invalid-status']['get']['x-apidog-status']);
    }

    public function test_injects_deprecated_status(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/deprecated' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-apidog-status')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('deprecated', $spec['paths']['/deprecated']['get']['x-apidog-status']);
    }

    // ── @custom-desc annotation ───────────────────────────────────────────────

    public function test_overrides_description_with_custom_desc(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/custom-desc' => ['post' => ['description' => 'Old description.']]],
        ]);

        $this->artisan('scribe:add-apidog-status')->assertSuccessful();

        $spec = $this->readYaml();
        $desc = $spec['paths']['/custom-desc']['post']['description'];
        $this->assertStringContainsString('Creates a new resource', $desc);
        $this->assertStringNotContainsString('Old description.', $desc);
    }

    // ── Multiple methods in spec ───────────────────────────────────────────────

    public function test_processes_all_http_methods(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => [
                '/released' => [
                    'get'  => [],
                    'post' => [],
                ],
            ],
        ]);

        $this->artisan('scribe:add-apidog-status')->assertSuccessful();

        $spec = $this->readYaml();
        // GET /released is registered in defineRoutes() → should get 'released'
        $this->assertSame('released', $spec['paths']['/released']['get']['x-apidog-status']);
        // POST /released is not registered → defaults to 'pending'
        $this->assertSame('pending', $spec['paths']['/released']['post']['x-apidog-status']);
    }

    // ── Edge cases ────────────────────────────────────────────────────────────

    public function test_handles_spec_with_no_paths(): void
    {
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:add-apidog-status')->assertSuccessful();
    }

    public function test_fails_when_yaml_not_found(): void
    {
        $this->artisan('scribe:add-apidog-status')->assertFailed();
    }
}
