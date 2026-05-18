<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class ScribeInjectJwtGenerator extends Command
{
    protected $signature = 'scribe:inject-jwt';
    protected $description = 'Deploy JWT Generator widget and inject mount points into OpenAPI info.description';

    private const BEGIN = '<!-- BEGIN: JWT Tools (auto-injected by scribe:inject-jwt) -->';
    private const END   = '<!-- END: JWT Tools -->';

    private const JWT_TOOLS_MARKDOWN = <<<'MD'
## API Secret Key

This API key will be automatically injected.

<div id="global-api-key-mount"></div>

## JWT Tools

### JWT Token Generator

Endpoints marked **requires authentication** need a JWT Bearer token. Use the tool below to generate one.

<div id="jwt-generator-mount"></div>

### JWT Validator

If you generated a JWT token elsewhere, you can paste it below to decode its payload and verify its signature against this environment's secret key.

<div id="jwt-validator-mount"></div>
MD;

    public function handle(): int
    {
        if ($this->deployJs() !== 0) {
            return 1;
        }

        return $this->injectYaml();
    }

    private function deployJs(): int
    {
        $jsSource = resource_path('scribe/assets/jwt-generator.js');
        $jsDest = public_path('api/jwt-generator.js');

        if (!file_exists($jsSource)) {
            $this->error('jwt-generator.js not found in resources/scribe/assets/');
            return 1;
        }

        $jsContent = file_get_contents($jsSource);
        if ($jsContent === false) {
            $this->error('Failed to read jwt-generator.js');
            return 1;
        }
        $jsContent = str_replace('__JWT_KEY_PLACEHOLDER__', config('openapi.features.jwtKey', ''), $jsContent);
        $jsContent = str_replace('__API_KEY_PLACEHOLDER__', config('openapi.features.apiKey', ''), $jsContent);

        if (!is_dir(public_path('api'))) {
            mkdir(public_path('api'), 0755, true);
        }

        file_put_contents($jsDest, $jsContent);
        $this->info('JWT Generator widget deployed to public/api/jwt-generator.js.');
        return 0;
    }

    private function injectYaml(): int
    {
        $type = config('scribe.type', 'laravel');
        $yamlPath = $type === 'static'
            ? base_path(rtrim(config('scribe.static.output_path', 'public/docs'), '/') . '/openapi.yaml')
            : storage_path('app/scribe/openapi.yaml');

        if (!file_exists($yamlPath)) {
            $this->error('openapi.yaml not found. Run scribe:generate first.');
            return 1;
        }

        $spec = Yaml::parseFile($yamlPath);
        $spec['info']['description'] = $this->injectBlock($spec['info']['description'] ?? '');

        file_put_contents(
            $yamlPath,
            Yaml::dump($spec, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $this->info('JWT Tools mount points injected into info.description.');
        return 0;
    }

    private function injectBlock(string $description): string
    {
        $description = (string) preg_replace(
            '/' . preg_quote(self::BEGIN, '/') . '.*?' . preg_quote(self::END, '/') . '\n*/s',
            '',
            $description
        );
        $description = rtrim($description);

        return ($description !== '' ? $description . "\n\n" : '')
            . self::BEGIN . "\n\n" . trim(self::JWT_TOOLS_MARKDOWN) . "\n\n" . self::END . "\n";
    }
}
