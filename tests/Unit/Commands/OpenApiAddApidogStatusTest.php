<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Commands\OpenApiAddApidogStatus;
use SrArchitects\ScribeToolkit\Tests\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OpenApiAddApidogStatusTest extends TestCase
{
    private function makeCommand(): StubOpenApiAddApidogStatus
    {
        $command = new StubOpenApiAddApidogStatus();
        $command->setLaravel($this->app);

        return $command;
    }

    /** @return array{code: int, output: string} */
    private function runCommand(StubOpenApiAddApidogStatus $command): array
    {
        $app = new ConsoleApplication();
        $app->add($command);
        $app->setAutoExit(false);
        $output = new BufferedOutput();
        $code   = $app->run(new StringInput('openapi:add-apidog-status'), $output);

        return ['code' => $code, 'output' => $output->fetch()];
    }

    public function test_delegates_to_scribe_command_when_api_overview_enabled(): void
    {
        config()->set('openapi.features.apiOverview', true);

        $command = $this->makeCommand();
        $result  = $this->runCommand($command);

        $this->assertSame(0, $result['code']);
        $this->assertSame(['scribe:add-apidog-status'], $command->calls);
    }

    public function test_skips_when_api_overview_disabled(): void
    {
        config()->set('openapi.features.apiOverview', false);

        $this->artisan('openapi:add-apidog-status')
            ->assertSuccessful()
            ->expectsOutputToContain('Skipped');
    }

    public function test_propagates_failure_exit_code(): void
    {
        config()->set('openapi.features.apiOverview', true);

        $command              = $this->makeCommand();
        $command->returnCode = 1;

        $result = $this->runCommand($command);

        $this->assertSame(1, $result['code']);
    }
}

class StubOpenApiAddApidogStatus extends OpenApiAddApidogStatus
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
