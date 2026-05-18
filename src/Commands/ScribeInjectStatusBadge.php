<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class ScribeInjectStatusBadge extends Command
{
    protected $signature = 'scribe:inject-status-badge';
    protected $description = 'Inject shield.io status badge images into each operation description in the OpenAPI YAML';

    private const STATUS_COLORS = [
        'designing'   => 'lightgrey',
        'pending'     => 'orange',
        'developing'  => 'blue',
        'integrating' => 'blueviolet',
        'testing'     => 'yellow',
        'tested'      => 'green',
        'released'    => 'brightgreen',
        'deprecated'  => 'red',
        'exception'   => 'critical',
        'obsolete'    => 'darkred',
    ];

    public function handle(): int
    {
        $type = config('scribe.type', 'laravel');
        if ($type === 'static') {
            $outputPath = (string) config('scribe.static.output_path', 'public/docs');
            $yamlPath = base_path(rtrim($outputPath, '/') . '/openapi.yaml');
        } else {
            $yamlPath = storage_path('app/scribe/openapi.yaml');
        }

        if (! file_exists($yamlPath)) {
            $this->error('openapi.yaml not found. Run php artisan scribe:generate first.');
            return self::FAILURE;
        }

        $spec = Yaml::parseFile($yamlPath);

        if (empty($spec['paths'])) {
            $this->warn('No paths found in OpenAPI spec.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($spec['paths'] as &$pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (! isset($pathItem[$method])) {
                    continue;
                }

                $status = (string) ($pathItem[$method]['x-apidog-status'] ?? 'pending');
                $color  = self::STATUS_COLORS[$status] ?? 'lightgrey';
                $badge  = "![API Status](https://img.shields.io/badge/status-{$status}-{$color})";

                $desc = (string) ($pathItem[$method]['description'] ?? '');
                $desc = (string) preg_replace('/^!\[API Status\]\(https:\/\/img\.shields\.io\/badge[^\)]+\)\n*/i', '', $desc);
                $desc = trim($desc);

                $pathItem[$method]['description'] = $desc !== '' ? "{$badge}\n\n{$desc}" : $badge;

                $count++;
            }
        }
        unset($pathItem);

        file_put_contents($yamlPath, Yaml::dump($spec, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $this->info("Injected shield.io status badge(s) into {$count} operation(s).");

        return self::SUCCESS;
    }
}
