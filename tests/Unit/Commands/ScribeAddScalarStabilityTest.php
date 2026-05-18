<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\Fixtures\TestController;
use SrArchitects\ScribeToolkit\Tests\TestCase;

class ScribeAddScalarStabilityTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);
        $router->get('api/experimental', [TestController::class, 'withExperimentalStability']);
    }

    public function test_injects_stability_from_annotation(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/stable' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-scalar-stability')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('stable', $spec['paths']['/stable']['get']['x-stability']);
    }

    public function test_injects_experimental_stability(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/experimental' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-scalar-stability')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertSame('experimental', $spec['paths']['/experimental']['get']['x-stability']);
    }

    public function test_does_not_inject_x_stability_for_unannotated_endpoints(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/plain' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-scalar-stability')->assertSuccessful();

        $spec = $this->readYaml();
        $this->assertArrayNotHasKey('x-stability', $spec['paths']['/plain']['get']);
    }

    public function test_skips_gracefully_when_no_annotations_found(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/plain' => ['get' => []]],
        ]);

        $this->artisan('scribe:add-scalar-stability')
            ->assertSuccessful()
            ->expectsOutputToContain('No matching paths in spec');
    }

    public function test_handles_spec_with_no_paths(): void
    {
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:add-scalar-stability')->assertSuccessful();
    }

    public function test_fails_when_yaml_not_found(): void
    {
        $this->artisan('scribe:add-scalar-stability')->assertFailed();
    }
}
