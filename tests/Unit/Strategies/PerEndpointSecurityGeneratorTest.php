<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Strategies;

use SrArchitects\ScribeToolkit\Strategies\PerEndpointSecurityGenerator;
use SrArchitects\ScribeToolkit\Tests\TestCase;

class PerEndpointSecurityGeneratorTest extends TestCase
{
    private PerEndpointSecurityGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = $this->app->make(PerEndpointSecurityGenerator::class);
    }

    // ── root() ────────────────────────────────────────────────────────────────

    public function test_root_removes_global_security_key(): void
    {
        $root = [
            'openapi'  => '3.1.0',
            'security' => [['global' => []]],
            'info'     => ['title' => 'Test'],
        ];

        $result = $this->generator->root($root, []);

        $this->assertArrayNotHasKey('security', $result);
        $this->assertArrayHasKey('openapi', $result);
        $this->assertArrayHasKey('info', $result);
    }

    public function test_root_is_no_op_when_no_global_security(): void
    {
        $root = ['openapi' => '3.1.0', 'info' => ['title' => 'Test']];

        $result = $this->generator->root($root, []);

        $this->assertSame($root, $result);
    }

    // ── applySecurityTo() — default (API key only) ────────────────────────────

    public function test_default_endpoint_gets_api_key_scheme(): void
    {
        $result = $this->generator->applySecurityTo([], false, false);

        $this->assertSame([['API_KEY' => []]], $result['security']);
    }

    // ── applySecurityTo() — @authenticated ───────────────────────────────────

    public function test_authenticated_endpoint_gets_api_key_and_jwt(): void
    {
        $result = $this->generator->applySecurityTo([], false, true);

        $this->assertSame([['API_KEY' => [], 'JWT_BEARER_TOKEN' => []]], $result['security']);
    }

    // ── applySecurityTo() — @noApiKey ─────────────────────────────────────────

    public function test_no_api_key_endpoint_gets_empty_security(): void
    {
        $result = $this->generator->applySecurityTo([], true, false);

        $this->assertSame([], $result['security']);
    }

    public function test_no_api_key_takes_precedence_over_authenticated(): void
    {
        $result = $this->generator->applySecurityTo([], true, true);

        $this->assertSame([], $result['security']);
    }

    // ── applySecurityTo() — configurable scheme names ─────────────────────────

    public function test_uses_custom_api_key_scheme_from_config(): void
    {
        config()->set('openapi.security.apiKeyScheme', 'MY_API_KEY');

        $result = $this->generator->applySecurityTo([], false, false);

        $this->assertSame([['MY_API_KEY' => []]], $result['security']);
    }

    public function test_uses_custom_jwt_scheme_from_config(): void
    {
        config()->set('openapi.security.apiKeyScheme', 'MY_API_KEY');
        config()->set('openapi.security.jwtScheme', 'MY_JWT');

        $result = $this->generator->applySecurityTo([], false, true);

        $this->assertSame([['MY_API_KEY' => [], 'MY_JWT' => []]], $result['security']);
    }

    // ── applySecurityTo() — optional header parameters ────────────────────────

    public function test_header_parameter_without_required_field_gets_required_true(): void
    {
        $pathItem = [
            'parameters' => [
                ['in' => 'header', 'name' => 'X-Publisher-Id'],
            ],
        ];

        $result = $this->generator->applySecurityTo($pathItem, false, false, []);

        $this->assertTrue($result['parameters'][0]['required']);
    }

    public function test_optional_header_gets_required_false(): void
    {
        $pathItem = [
            'parameters' => [
                ['in' => 'header', 'name' => 'X-Publisher-Id'],
            ],
        ];

        $result = $this->generator->applySecurityTo($pathItem, false, false, ['x-publisher-id']);

        $this->assertFalse($result['parameters'][0]['required']);
    }

    public function test_header_matching_is_case_insensitive(): void
    {
        $pathItem = [
            'parameters' => [
                ['in' => 'header', 'name' => 'X-PUBLISHER-ID'],
            ],
        ];

        $result = $this->generator->applySecurityTo($pathItem, false, false, ['x-publisher-id']);

        $this->assertFalse($result['parameters'][0]['required']);
    }

    public function test_already_required_header_is_not_overridden(): void
    {
        $pathItem = [
            'parameters' => [
                ['in' => 'header', 'name' => 'X-Required', 'required' => true],
            ],
        ];

        $result = $this->generator->applySecurityTo($pathItem, false, false, ['x-required']);

        // array_key_exists('required') is true → not overridden
        $this->assertTrue($result['parameters'][0]['required']);
    }

    public function test_query_parameter_is_not_modified(): void
    {
        $pathItem = [
            'parameters' => [
                ['in' => 'query', 'name' => 'page'],
            ],
        ];

        $result = $this->generator->applySecurityTo($pathItem, false, false, []);

        $this->assertArrayNotHasKey('required', $result['parameters'][0]);
    }

    public function test_no_parameters_key_leaves_path_item_unchanged(): void
    {
        $pathItem = ['operationId' => 'test'];

        $result = $this->generator->applySecurityTo($pathItem, false, false, []);

        $this->assertArrayNotHasKey('parameters', $result);
    }
}
