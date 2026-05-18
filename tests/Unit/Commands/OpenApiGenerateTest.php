<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Commands\OpenApiGenerate;
use SrArchitects\ScribeToolkit\Tests\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OpenApiGenerateTest extends TestCase
{
    private function makeCommand(): StubOpenApiGenerate
    {
        $command = new StubOpenApiGenerate();
        $command->setLaravel($this->app);

        return $command;
    }

    /** @return array{code: int} */
    private function runCommand(StubOpenApiGenerate $command): array
    {
        $app = new ConsoleApplication();
        $app->add($command);
        $app->setAutoExit(false);
        $code = $app->run(new StringInput('openapi:generate'), new BufferedOutput());

        return ['code' => $code];
    }

    public function test_delegates_to_scribe_generate(): void
    {
        $command = $this->makeCommand();
        $result  = $this->runCommand($command);

        $this->assertSame(0, $result['code']);
        $this->assertSame(['scribe:generate'], $command->calls);
    }

    public function test_propagates_failure_exit_code(): void
    {
        $command             = $this->makeCommand();
        $command->returnCode = 1;

        $result = $this->runCommand($command);

        $this->assertSame(1, $result['code']);
    }
}

class StubOpenApiGenerate extends OpenApiGenerate
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
