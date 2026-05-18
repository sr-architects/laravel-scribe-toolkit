<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Strategies;

use SrArchitects\ScribeToolkit\Strategies\StripApiPrefix;
use SrArchitects\ScribeToolkit\Tests\TestCase;

class StripApiPrefixTest extends TestCase
{
    private StripApiPrefix $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = $this->app->make(StripApiPrefix::class);
    }

    public function test_does_not_strip_in_local_environment(): void
    {
        config()->set('app.env', 'local');

        $endpoint       = new \stdClass();
        $endpoint->uri  = 'api/users';

        // Create a mock that satisfies the type hint
        $mock = $this->createPartialMockWithUri('api/users');

        $result = ($this->strategy)($mock, []);

        $this->assertNull($result);
        $this->assertSame('api/users', $mock->uri);
    }

    public function test_strips_api_prefix_in_production(): void
    {
        config()->set('app.env', 'production');

        $mock = $this->createPartialMockWithUri('api/users');

        ($this->strategy)($mock, []);

        $this->assertSame('users', $mock->uri);
    }

    public function test_strips_api_prefix_in_staging(): void
    {
        config()->set('app.env', 'staging');

        $mock = $this->createPartialMockWithUri('api/products/123');

        ($this->strategy)($mock, []);

        $this->assertSame('products/123', $mock->uri);
    }

    public function test_does_not_modify_uri_without_api_prefix(): void
    {
        config()->set('app.env', 'production');

        $mock = $this->createPartialMockWithUri('users');

        ($this->strategy)($mock, []);

        $this->assertSame('users', $mock->uri);
    }

    public function test_always_returns_null(): void
    {
        config()->set('app.env', 'production');
        $mock = $this->createPartialMockWithUri('api/test');

        $result = ($this->strategy)($mock, []);

        $this->assertNull($result);
    }

    /**
     * Creates a partial mock that looks like ExtractedEndpointData for uri access.
     */
    private function createPartialMockWithUri(string $uri): object
    {
        $mock      = \Mockery::mock(\Knuckles\Camel\Extraction\ExtractedEndpointData::class)->makePartial();
        $mock->uri = $uri;

        return $mock;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
