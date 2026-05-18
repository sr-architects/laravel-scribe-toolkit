<?php

namespace SrArchitects\ScribeToolkit\Tests\Unit\Commands;

use SrArchitects\ScribeToolkit\Commands\OpenApiInjectJwtTools;
use SrArchitects\ScribeToolkit\Tests\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OpenApiInjectJwtToolsTest extends TestCase
{
    private string $jsSourceDir;
    private string $jsSourcePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jsSourceDir  = resource_path('scribe/assets');
        $this->jsSourcePath = $this->jsSourceDir . '/jwt-generator.js';

        if (! is_dir($this->jsSourceDir)) {
            mkdir($this->jsSourceDir, 0755, true);
        }

        file_put_contents($this->jsSourcePath, implode("\n", [
            '(function () {',
            '  /* BEGIN: CONFIG (auto-updated by openapi:inject-jwt-tools from config/openapi.php) */',
            '  var CONFIG = {',
            '    jwtTools: {',
            '      globalApiKeyWidget:      true,',
            '      jwtTokenGeneratorWidget: true,',
            '      jwtValidatorWidget:      true,',
            '    },',
            '    jwtDefaultPayload: {},',
            '  };',
            '  /* END: CONFIG */',
            '})();',
        ]));
    }

    private function makeCommand(): StubOpenApiInjectJwtTools
    {
        $command = new StubOpenApiInjectJwtTools();
        $command->setLaravel($this->app);

        return $command;
    }

    /** @return array{code: int, output: string} */
    private function runCommand(StubOpenApiInjectJwtTools $command): array
    {
        $app = new ConsoleApplication();
        $app->add($command);
        $app->setAutoExit(false);
        $output = new BufferedOutput();
        $code   = $app->run(new StringInput('openapi:inject-jwt-tools'), $output);

        return ['code' => $code, 'output' => $output->fetch()];
    }

    public function test_skips_when_all_jwt_tools_disabled(): void
    {
        config()->set('openapi.features.jwtTools', [
            'globalApiKeyWidget'      => false,
            'jwtTokenGeneratorWidget' => false,
            'jwtValidatorWidget'      => false,
        ]);

        $command = $this->makeCommand();
        $result  = $this->runCommand($command);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('Skipped', $result['output']);
        $this->assertEmpty($command->calls);
    }

    public function test_syncs_config_block_and_delegates_to_scribe_inject_jwt(): void
    {
        config()->set('openapi.features.jwtTools', [
            'globalApiKeyWidget'      => true,
            'jwtTokenGeneratorWidget' => false,
            'jwtValidatorWidget'      => true,
        ]);
        config()->set('openapi.features.jwtDefaultPayload', ['user_id' => '123']);

        $command = $this->makeCommand();
        $this->runCommand($command);

        $js = file_get_contents($this->jsSourcePath);
        $this->assertIsString($js);
        $this->assertStringContainsString('jwtTokenGeneratorWidget: false', $js);
        $this->assertStringContainsString('"user_id":"123"', $js);

        $this->assertSame(['scribe:inject-jwt'], $command->calls);
    }

    public function test_proceeds_without_warning_when_js_missing(): void
    {
        unlink($this->jsSourcePath);

        $command = $this->makeCommand();
        $result  = $this->runCommand($command);

        $this->assertSame(0, $result['code']);
        $this->assertSame(['scribe:inject-jwt'], $command->calls);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->jsSourcePath)) {
            unlink($this->jsSourcePath);
        }

        parent::tearDown();
    }
}

class StubOpenApiInjectJwtTools extends OpenApiInjectJwtTools
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
