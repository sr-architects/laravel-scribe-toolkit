<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Commands\OpenApiAddScalarStability;
use SrArchitects\ScribeToolkit\Tests\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OpenApiAddScalarStabilityTest extends TestCase
{
    private function makeCommand(): StubOpenApiAddScalarStability
    {
        $command = new StubOpenApiAddScalarStability();
        $command->setLaravel($this->app);

        return $command;
    }

    /** @return array{code: int} */
    private function runCommand(StubOpenApiAddScalarStability $command): array
    {
        $app = new ConsoleApplication();
        $app->add($command);
        $app->setAutoExit(false);
        $code = $app->run(new StringInput('openapi:add-scalar-stability'), new BufferedOutput());

        return ['code' => $code];
    }

    public function test_delegates_to_scribe_command(): void
    {
        $command = $this->makeCommand();
        $result  = $this->runCommand($command);

        $this->assertSame(0, $result['code']);
        $this->assertSame(['scribe:add-scalar-stability'], $command->calls);
    }

    public function test_propagates_failure(): void
    {
        $command             = $this->makeCommand();
        $command->returnCode = 1;

        $result = $this->runCommand($command);

        $this->assertSame(1, $result['code']);
    }
}

class StubOpenApiAddScalarStability extends OpenApiAddScalarStability
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
