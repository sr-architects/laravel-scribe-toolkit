<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OpenApiGenerate extends Command
{
    protected $signature = 'openapi:generate';
    protected $description = 'Generate OpenAPI spec via Scribe (scribe:generate)';

    public function handle(): int
    {
        $this->info('Running scribe:generate...');
        return $this->runStep('scribe:generate');
    }

    protected function runStep(string $command): int
    {
        return Artisan::call($command, [], $this->output);
    }
}
