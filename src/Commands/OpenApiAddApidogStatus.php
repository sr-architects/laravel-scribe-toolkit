<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OpenApiAddApidogStatus extends Command
{
    protected $signature = 'openapi:add-apidog-status';
    protected $description = 'Inject x-apidog-status into OpenAPI spec (scribe:add-apidog-status)';

    public function handle(): int
    {
        if (!config('openapi.features.apiOverview', true)) {
            $this->line('⏭  Skipped (apiOverview: false)');
            return self::SUCCESS;
        }

        return $this->runStep('scribe:add-apidog-status');
    }

    protected function runStep(string $command): int
    {
        return Artisan::call($command, [], $this->output);
    }
}
