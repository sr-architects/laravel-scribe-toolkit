<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Commands\OpenApiPublish;
use SrArchitects\ScribeToolkit\Tests\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

class OpenApiPublishTest extends TestCase
{
    private const PIPELINE = [
        'openapi:generate',
        'openapi:add-apidog-status',
        'openapi:inject-status-badge',
        'openapi:add-scalar-stability',
        'openapi:inject-environments',
        'openapi:inject-jwt-tools',
        'openapi:export',
    ];

    private function makeCommand(): StubOpenApiPublish
    {
        $command = new StubOpenApiPublish();
        $command->setLaravel($this->app);

        return $command;
    }

    /** @return array{code: int, output: string} */
    private function runCommand(StubOpenApiPublish $command): array
    {
        $app = new ConsoleApplication();
        $app->add($command);
        $app->setAutoExit(false);
        $output = new BufferedOutput();
        $code   = $app->run(new StringInput('openapi:publish'), $output);

        return ['code' => $code, 'output' => $output->fetch()];
    }

    public function test_runs_all_seven_steps_in_order(): void
    {
        $command = $this->makeCommand();
        $result  = $this->runCommand($command);

        $this->assertSame(0, $result['code']);
        $this->assertSame(self::PIPELINE, $command->calls);
    }

    public function test_stops_pipeline_when_first_step_fails(): void
    {
        $command                              = $this->makeCommand();
        $command->returnCodes['openapi:generate'] = 1;

        $result = $this->runCommand($command);

        $this->assertSame(1, $result['code']);
        $this->assertSame(['openapi:generate'], $command->calls);
    }

    public function test_stops_pipeline_when_intermediate_step_fails(): void
    {
        $command                                          = $this->makeCommand();
        $command->returnCodes['openapi:inject-environments'] = 1;

        $result = $this->runCommand($command);

        $this->assertSame(1, $result['code']);
        $this->assertSame(array_slice(self::PIPELINE, 0, 5), $command->calls);
    }

    public function test_propagates_non_zero_exit_code(): void
    {
        $command                              = $this->makeCommand();
        $command->returnCodes['openapi:generate'] = 2;

        $result = $this->runCommand($command);

        $this->assertSame(2, $result['code']);
    }
}

class StubOpenApiPublish extends OpenApiPublish
{
    /** @var list<string> */
    public array $calls = [];
    /** @var array<string, int> */
    public array $returnCodes = [];

    protected function runStep(string $command): int
    {
        $this->calls[] = $command;

        return $this->returnCodes[$command] ?? 0;
    }
}
