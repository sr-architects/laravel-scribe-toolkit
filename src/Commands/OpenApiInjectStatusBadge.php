<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OpenApiInjectStatusBadge extends Command
{
    protected $signature = 'openapi:inject-status-badge';
    protected $description = 'Inject shield.io status badges into OpenAPI spec (scribe:inject-status-badge)';

    public function handle(): int
    {
        if (! config('openapi.features.apiOverview', true) || ! config('openapi.features.statusBadges', true)) {
            $this->line('⏭  Skipped');

            return self::SUCCESS;
        }

        return $this->runStep('scribe:inject-status-badge');
    }

    protected function runStep(string $command): int
    {
        return Artisan::call($command, [], $this->output);
    }
}
