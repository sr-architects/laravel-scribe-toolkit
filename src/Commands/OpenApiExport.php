<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class OpenApiExport extends Command
{
    protected $signature = 'openapi:export';
    protected $description = 'Write a clean export-ready OpenAPI spec (strips Scalar widgets, badge images)';

    private const SCALAR_KEYS = ['x-scalar-environments'];

    public function handle(): int
    {
        $type       = config('scribe.type', 'laravel');
        $basePath   = $type === 'static'
            ? base_path(rtrim(config('scribe.static.output_path', 'public/docs'), '/'))
            : storage_path('app/scribe');

        $sourcePath = $basePath . '/openapi.yaml';
        $exportPath = $basePath . '/openapi.export.yaml';

        if (! file_exists($sourcePath)) {
            $this->error('openapi.yaml not found. Run openapi:generate first.');
            return self::FAILURE;
        }

        $spec = Yaml::parseFile($sourcePath);

        foreach (self::SCALAR_KEYS as $key) {
            unset($spec[$key]);
        }

        if (isset($spec['info']['description'])) {
            $spec['info']['description'] = $this->stripJwtToolsBlock($spec['info']['description']);
            $spec['info']['description'] = $this->stripEnvironmentsBlock($spec['info']['description']);
        }

        if (! empty($spec['paths'])) {
            foreach ($spec['paths'] as &$pathItem) {
                foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                    if (! isset($pathItem[$method]['description'])) {
                        continue;
                    }
                    $pathItem[$method]['description'] = $this->stripBadgeImages($pathItem[$method]['description']);
                }
            }
            unset($pathItem);
        }

        file_put_contents(
            $exportPath,
            Yaml::dump($spec, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $this->info("Export spec written to: {$exportPath}");
        $this->line('  Import URL: <href=' . url('/api/spec/openapi.export.yaml') . '>' . url('/api/spec/openapi.export.yaml') . '</>');

        return self::SUCCESS;
    }

    private function stripJwtToolsBlock(string $description): string
    {
        $begin = '<!-- BEGIN: JWT Tools (auto-injected by scribe:inject-jwt) -->';
        $end   = '<!-- END: JWT Tools -->';

        $description = (string) preg_replace(
            '/' . preg_quote($begin, '/') . '.*?' . preg_quote($end, '/') . '\n*/s',
            '',
            $description
        );

        return trim($description);
    }

    private function stripEnvironmentsBlock(string $description): string
    {
        $begin = '<!-- BEGIN: Environments (auto-injected by openapi:inject-environments) -->';
        $end   = '<!-- END: Environments -->';

        $description = (string) preg_replace(
            '/' . preg_quote($begin, '/') . '.*?' . preg_quote($end, '/') . '\n*/s',
            '',
            $description
        );

        return trim($description);
    }

    private function stripBadgeImages(string $description): string
    {
        $description = (string) preg_replace(
            '/^!\[[^\]]*\]\(https:\/\/img\.shields\.io\/badge[^\)]*\)\n*/im',
            '',
            $description
        );

        return trim($description);
    }
}
