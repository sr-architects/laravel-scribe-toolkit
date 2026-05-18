<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OpenApiAddScalarStability extends Command
{
    protected $signature = 'openapi:add-scalar-stability';
    protected $description = 'Inject x-stability from @custom-stability docblock annotations (scribe:add-scalar-stability)';

    public function handle(): int
    {
        return Artisan::call('scribe:add-scalar-stability', [], $this->output);
    }
}
