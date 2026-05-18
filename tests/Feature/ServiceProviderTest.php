<?php

namespace SrArchitects\ScribeToolkit\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use SrArchitects\ScribeToolkit\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    // ── Commands registration ─────────────────────────────────────────────────

    #[DataProvider('commandProvider')]
    public function test_all_commands_are_registered(string $command): void
    {
        // --help is intercepted by Symfony before execute(), so no YAML/file setup needed.
        $this->artisan($command, ['--help'])->assertSuccessful();
    }

    /** @return array<string, array{string}> */
    public static function commandProvider(): array
    {
        return [
            'openapi:publish'             => ['openapi:publish'],
            'openapi:generate'            => ['openapi:generate'],
            'openapi:export'              => ['openapi:export'],
            'openapi:add-apidog-status'   => ['openapi:add-apidog-status'],
            'openapi:add-scalar-stability'=> ['openapi:add-scalar-stability'],
            'openapi:inject-environments' => ['openapi:inject-environments'],
            'openapi:inject-jwt-tools'    => ['openapi:inject-jwt-tools'],
            'openapi:inject-status-badge' => ['openapi:inject-status-badge'],
            'scribe:add-apidog-status'    => ['scribe:add-apidog-status'],
            'scribe:add-scalar-stability' => ['scribe:add-scalar-stability'],
            'scribe:inject-jwt'           => ['scribe:inject-jwt'],
            'scribe:inject-report'        => ['scribe:inject-report'],
            'scribe:inject-status-badge'  => ['scribe:inject-status-badge'],
        ];
    }

    // ── Config merging ────────────────────────────────────────────────────────

    public function test_openapi_config_is_merged(): void
    {
        $this->assertNotNull(config('openapi'));
        $this->assertIsArray(config('openapi.features'));
        $this->assertIsArray(config('openapi.features.jwtTools'));
        $this->assertIsArray(config('openapi.environments'));
        $this->assertIsArray(config('openapi.security'));
    }

    public function test_jwt_tools_config_has_correct_defaults(): void
    {
        $jwtTools = config('openapi.features.jwtTools');

        $this->assertTrue($jwtTools['globalApiKeyWidget']);
        $this->assertTrue($jwtTools['jwtTokenGeneratorWidget']);
        $this->assertTrue($jwtTools['jwtValidatorWidget']);
    }

    public function test_security_config_has_generic_defaults(): void
    {
        $this->assertSame('API_KEY', config('openapi.security.apiKeyScheme'));
        $this->assertSame('JWT_BEARER_TOKEN', config('openapi.security.jwtScheme'));
    }

    // ── Strategy auto-injection ───────────────────────────────────────────────

    public function test_no_api_key_extractor_is_auto_injected_into_scribe_metadata(): void
    {
        $metadata = config('scribe.strategies.metadata', []);

        $this->assertContains(
            \SrArchitects\ScribeToolkit\Strategies\NoApiKeyExtractor::class,
            $metadata
        );
    }

    public function test_optional_header_extractor_is_auto_injected_into_scribe_headers(): void
    {
        $headers = config('scribe.strategies.headers', []);

        $this->assertContains(
            \SrArchitects\ScribeToolkit\Strategies\OptionalHeaderExtractor::class,
            $headers
        );
    }

    public function test_optional_header_meta_extractor_is_auto_injected_into_scribe_headers(): void
    {
        $headers = config('scribe.strategies.headers', []);

        $this->assertContains(
            \SrArchitects\ScribeToolkit\Strategies\OptionalHeaderMetaExtractor::class,
            $headers
        );
    }

    public function test_no_injection_when_scribe_config_absent(): void
    {
        // Override scribe config to be empty
        config(['scribe' => []]);

        // Manually trigger the inject (simulate boot on a fresh app without Scribe)
        $provider = new \SrArchitects\ScribeToolkit\ScribeToolkitServiceProvider($this->app);
        // Should not throw — guard against empty scribe config
        $provider->boot();

        $this->assertEmpty(config('scribe.strategies.metadata', []));
    }
}
