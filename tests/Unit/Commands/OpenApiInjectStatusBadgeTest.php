<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Commands\OpenApiInjectStatusBadge;
use SrArchitects\ScribeToolkit\Tests\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OpenApiInjectStatusBadgeTest extends TestCase
{
    private function makeCommand(): StubOpenApiInjectStatusBadge
    {
        $command = new StubOpenApiInjectStatusBadge();
        $command->setLaravel($this->app);

        return $command;
    }

    /** @return array{code: int} */
    private function runCommand(StubOpenApiInjectStatusBadge $command): array
    {
        $app = new ConsoleApplication();
        $app->add($command);
        $app->setAutoExit(false);
        $code = $app->run(new StringInput('openapi:inject-status-badge'), new BufferedOutput());

        return ['code' => $code];
    }

    public function test_delegates_when_both_flags_enabled(): void
    {
        config()->set('openapi.features.apiOverview', true);
        config()->set('openapi.features.statusBadges', true);

        $command = $this->makeCommand();
        $result  = $this->runCommand($command);

        $this->assertSame(0, $result['code']);
        $this->assertSame(['scribe:inject-status-badge'], $command->calls);
    }

    public function test_skips_when_api_overview_disabled(): void
    {
        config()->set('openapi.features.apiOverview', false);

        $this->artisan('openapi:inject-status-badge')
            ->assertSuccessful()
            ->expectsOutputToContain('Skipped');
    }

    public function test_skips_when_status_badges_disabled(): void
    {
        config()->set('openapi.features.apiOverview', true);
        config()->set('openapi.features.statusBadges', false);

        $this->artisan('openapi:inject-status-badge')
            ->assertSuccessful()
            ->expectsOutputToContain('Skipped');
    }
}

class StubOpenApiInjectStatusBadge extends OpenApiInjectStatusBadge
{
    /** @var list<string> */
    public array $calls = [];
    public int $returnCode    = 0;

    protected function runStep(string $command): int
    {
        $this->calls[] = $command;

        return $this->returnCode;
    }
}
