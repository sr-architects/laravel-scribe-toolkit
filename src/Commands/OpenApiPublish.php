<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OpenApiPublish extends Command
{
    protected $signature = 'openapi:publish';
    protected $description = 'Generate, annotate, and deploy OpenAPI docs (generate → add-apidog-status → inject-status-badge → add-scalar-stability → inject-environments → inject-jwt-tools → export)';

    public function handle(): int
    {
        $steps = [
            ['openapi:generate',              'Generating OpenAPI spec...'],
            ['openapi:add-apidog-status',     'Injecting x-apidog-status...'],
            ['openapi:inject-status-badge',   'Injecting shield.io status badges...'],
            ['openapi:add-scalar-stability',  'Injecting x-stability markers...'],
            ['openapi:inject-environments',   'Injecting x-scalar-environments...'],
            ['openapi:inject-jwt-tools',      'Deploying JWT Tools widget...'],
            ['openapi:export',                'Writing clean export spec...'],
        ];

        foreach ($steps as [$command, $message]) {
            $this->info($message);
            $exitCode = $this->runStep($command);
            if ($exitCode !== 0) {
                return $exitCode;
            }
        }

        $this->newLine();
        $this->line('✅  Docs: <href=/api/docs>/api/docs</>');

        return self::SUCCESS;
    }

    protected function runStep(string $command): int
    {
        return Artisan::call($command, [], $this->output);
    }
}
