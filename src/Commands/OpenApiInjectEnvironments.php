<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class OpenApiInjectEnvironments extends Command
{
    protected $signature = 'openapi:inject-environments';
    protected $description = 'Inject x-scalar-environments and publisher ID table into OpenAPI spec';

    private const BEGIN = '<!-- BEGIN: Environments (auto-injected by openapi:inject-environments) -->';
    private const END   = '<!-- END: Environments -->';

    public function handle(): int
    {
        $environments = array_filter(
            config('openapi.environments', []),
            fn ($env) => ! empty($env['url'])
        );

        if (empty($environments)) {
            $this->line('⏭  Skipped (no environments configured)');
            return self::SUCCESS;
        }

        $type     = config('scribe.type', 'laravel');
        $yamlPath = $type === 'static'
            ? base_path(rtrim(config('scribe.static.output_path', 'public/docs'), '/') . '/openapi.yaml')
            : storage_path('app/scribe/openapi.yaml');

        if (! file_exists($yamlPath)) {
            $this->error('openapi.yaml not found. Run openapi:generate first.');
            return self::FAILURE;
        }

        $spec = Yaml::parseFile($yamlPath);

        $spec['x-scalar-environments'] = array_values(array_map(function ($env) {
            $entry = [
                'name'    => $env['name'],
                'color'   => $env['color'] ?? '#6366F1',
                'servers' => [['url' => rtrim($env['url'], '/')]],
            ];

            if (! empty($env['publisherId'])) {
                $entry['variables'] = [
                    ['name' => 'publisherId', 'value' => $env['publisherId']],
                ];
            }

            return $entry;
        }, $environments));

        $spec['info']['description'] = $this->injectPublisherTable(
            $spec['info']['description'] ?? '',
            array_values($environments)
        );

        file_put_contents(
            $yamlPath,
            Yaml::dump($spec, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $this->table(
            ['Name', 'URL', 'publisherId'],
            array_map(fn ($e) => [$e['name'], $e['url'], $e['publisherId'] ?? '—'], array_values($environments))
        );
        $this->info('x-scalar-environments and publisher ID table injected.');

        return self::SUCCESS;
    }

    private function injectPublisherTable(string $description, array $environments): string
    {
        $description = (string) preg_replace(
            '/' . preg_quote(self::BEGIN, '/') . '.*?' . preg_quote(self::END, '/') . '\n*/s',
            '',
            $description
        );

        $withIds = array_filter($environments, fn ($e) => ! empty($e['publisherId']));

        if (empty($withIds)) {
            return trim($description);
        }

        $rows = ["| Environment | Publisher ID |", "|---|---|"];
        foreach ($withIds as $env) {
            $rows[] = "| **{$env['name']}** | `{$env['publisherId']}` |";
        }

        $block = self::BEGIN . "\n\n"
            . "## Publisher IDs\n\n"
            . implode("\n", $rows) . "\n\n"
            . self::END . "\n";

        $trimmed = trim($description);
        return ($trimmed !== '' ? $trimmed . "\n\n" : '') . $block;
    }
}
