<?php

namespace SrArchitects\ScribeToolkit\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SrArchitects\ScribeToolkit\ScribeToolkitServiceProvider;
use SrArchitects\ScribeToolkit\Tests\Fixtures\TestController;
use Symfony\Component\Yaml\Yaml;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ScribeToolkitServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use laravel (storage) mode so commands resolve paths via storage_path()
        $app['config']->set('scribe.type', 'laravel');
        $app['config']->set('scribe.static.output_path', 'public/docs');

        // Default route prefix used by stripRoutePrefix() in docblock commands
        $app['config']->set('scribe.routes.0.match.prefixes', ['api/*']);

        // Default openapi config
        $app['config']->set('openapi.features.jwtTools', [
            'globalApiKeyWidget'      => true,
            'jwtTokenGeneratorWidget' => true,
            'jwtValidatorWidget'      => true,
        ]);
        $app['config']->set('openapi.features.jwtKey', 'test-jwt-secret');
        $app['config']->set('openapi.features.apiKey', 'test-api-key');
        $app['config']->set('openapi.features.jwtDefaultPayload', []);
        $app['config']->set('openapi.features.apiOverview', true);
        $app['config']->set('openapi.features.statusBadges', true);
        $app['config']->set('openapi.environments', []);
        $app['config']->set('openapi.security.apiKeyScheme', 'API_KEY');
        $app['config']->set('openapi.security.jwtScheme', 'JWT_BEARER_TOKEN');
    }

    protected function defineRoutes($router): void
    {
        $router->get('api/released', [TestController::class, 'withReleasedStatus']);
        $router->get('api/stable', [TestController::class, 'withStableStability']);
        $router->post('api/custom-desc', [TestController::class, 'withCustomDesc']);
        $router->get('api/plain', [TestController::class, 'plain']);
    }

    // ── YAML helpers ─────────────────────────────────────────────────────────

    protected function yamlDir(): string
    {
        return storage_path('app/scribe');
    }

    protected function yamlPath(): string
    {
        return $this->yamlDir() . '/openapi.yaml';
    }

    protected function exportPath(): string
    {
        return $this->yamlDir() . '/openapi.export.yaml';
    }

    /** @param array<string, mixed> $spec */
    protected function writeYaml(array $spec): void
    {
        if (! is_dir($this->yamlDir())) {
            mkdir($this->yamlDir(), 0755, true);
        }
        file_put_contents($this->yamlPath(), Yaml::dump($spec, 20, 2));
    }

    /** @return array<string, mixed> */
    protected function readYaml(): array
    {
        /** @var array<string, mixed> $spec */
        $spec = Yaml::parseFile($this->yamlPath());

        return $spec;
    }

    /** @return array<string, mixed> */
    protected function readExportYaml(): array
    {
        /** @var array<string, mixed> $spec */
        $spec = Yaml::parseFile($this->exportPath());

        return $spec;
    }

    /**
     * Returns a minimal valid OpenAPI spec.
     * Does NOT include a 'paths' key so that array-union (`+`) in tests
     * correctly picks up the caller's 'paths' value.
     *
     * @return array<string, mixed>
     */
    protected function minimalSpec(string $description = ''): array
    {
        return [
            'openapi' => '3.1.0',
            'info'    => [
                'title'       => 'Test API',
                'version'     => '1.0.0',
                'description' => $description,
            ],
        ];
    }

    protected function tearDown(): void
    {
        foreach ([$this->yamlPath(), $this->exportPath()] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }
}
