<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Tests\TestCase;

class ScribeInjectStatusBadgeTest extends TestCase
{
    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_injects_badge_into_operation_description(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => [
                '/users' => [
                    'get' => [
                        'x-apidog-status' => 'released',
                        'description'     => 'List all users.',
                    ],
                ],
            ],
        ]);

        $this->artisan('scribe:inject-status-badge')->assertSuccessful();

        $spec = $this->readYaml();
        $desc = $spec['paths']['/users']['get']['description'];

        $this->assertStringStartsWith('![API Status](https://img.shields.io/badge/status-released-brightgreen)', $desc);
        $this->assertStringContainsString('List all users.', $desc);
    }

    public function test_uses_pending_color_when_status_absent(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/items' => ['get' => ['description' => 'Items.']]],
        ]);

        $this->artisan('scribe:inject-status-badge')->assertSuccessful();

        $desc = $this->readYaml()['paths']['/items']['get']['description'];

        $this->assertStringContainsString('status-pending-orange', $desc);
    }

    public function test_correct_colors_for_all_statuses(): void
    {
        $cases = [
            'designing'   => 'lightgrey',
            'developing'  => 'blue',
            'integrating' => 'blueviolet',
            'testing'     => 'yellow',
            'tested'      => 'green',
            'released'    => 'brightgreen',
            'deprecated'  => 'red',
            'exception'   => 'critical',
            'obsolete'    => 'darkred',
        ];

        $paths = [];
        foreach ($cases as $status => $color) {
            $paths["/{$status}"] = ['get' => ['x-apidog-status' => $status]];
        }

        $this->writeYaml($this->minimalSpec() + ['paths' => $paths]);
        $this->artisan('scribe:inject-status-badge')->assertSuccessful();

        $spec = $this->readYaml();
        foreach ($cases as $status => $color) {
            $desc = $spec['paths']["/{$status}"]['get']['description'];
            $this->assertStringContainsString("status-{$status}-{$color}", $desc, "Wrong color for {$status}");
        }
    }

    public function test_is_idempotent_does_not_double_badge(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/ping' => ['get' => ['x-apidog-status' => 'released']]],
        ]);

        $this->artisan('scribe:inject-status-badge')->assertSuccessful();
        $this->artisan('scribe:inject-status-badge')->assertSuccessful();

        $desc = $this->readYaml()['paths']['/ping']['get']['description'];

        // Badge should appear exactly once
        $this->assertSame(1, substr_count($desc, '![API Status]'));
    }

    public function test_badge_only_when_description_is_empty(): void
    {
        $this->writeYaml($this->minimalSpec() + [
            'paths' => ['/ping' => ['get' => ['x-apidog-status' => 'released']]],
        ]);

        $this->artisan('scribe:inject-status-badge')->assertSuccessful();

        $desc = $this->readYaml()['paths']['/ping']['get']['description'];
        $this->assertStringStartsWith('![API Status]', $desc);
        $this->assertSame(0, substr_count($desc, "\n\n"));  // badge only — no trailing blank line
    }

    public function test_handles_spec_with_no_paths(): void
    {
        $this->writeYaml($this->minimalSpec());

        $this->artisan('scribe:inject-status-badge')->assertSuccessful();
    }

    // ── Error path ────────────────────────────────────────────────────────────

    public function test_fails_when_yaml_not_found(): void
    {
        $this->artisan('scribe:inject-status-badge')->assertFailed();
    }
}
