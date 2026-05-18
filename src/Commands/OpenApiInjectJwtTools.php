<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OpenApiInjectJwtTools extends Command
{
    protected $signature = 'openapi:inject-jwt-tools';
    protected $description = 'Deploy JWT Tools widget to Scalar docs (scribe:inject-jwt)';

    public function handle(): int
    {
        $jwtTools = config('openapi.features.jwtTools', []);
        $anyEnabled = ($jwtTools['globalApiKeyWidget'] ?? true)
            || ($jwtTools['jwtTokenGeneratorWidget'] ?? true)
            || ($jwtTools['jwtValidatorWidget'] ?? true);

        if (!$anyEnabled) {
            $this->line('⏭  Skipped (all jwtTools disabled)');
            return self::SUCCESS;
        }

        // Sync the CONFIG block in jwt-generator.js from config/openapi.php
        // so PHP config is the single source of truth.
        $jsPath = resource_path('scribe/assets/jwt-generator.js');
        if (file_exists($jsPath)) {
            $globalApiKey = ($jwtTools['globalApiKeyWidget'] ?? true) ? 'true' : 'false';
            $jwtGen       = ($jwtTools['jwtTokenGeneratorWidget'] ?? true) ? 'true' : 'false';
            $jwtVal       = ($jwtTools['jwtValidatorWidget'] ?? true) ? 'true' : 'false';

            $defaultPayload = config('openapi.features.jwtDefaultPayload', []);
            $defaultPayloadJson = json_encode($defaultPayload, JSON_UNESCAPED_UNICODE);

            $configBlock = "  /* BEGIN: CONFIG (auto-updated by openapi:inject-jwt-tools from config/openapi.php) */\n"
                . "  var CONFIG = {\n"
                . "    jwtTools: {\n"
                . "      globalApiKeyWidget:      {$globalApiKey},\n"
                . "      jwtTokenGeneratorWidget: {$jwtGen},\n"
                . "      jwtValidatorWidget:      {$jwtVal},\n"
                . "    },\n"
                . "    jwtDefaultPayload: {$defaultPayloadJson},\n"
                . "  };\n"
                . "  /* END: CONFIG */";

            $js = file_get_contents($jsPath);
            if ($js === false) {
                $this->warn('⚠️  Could not read jwt-generator.js — skipping CONFIG sync.');
            } else {
                $replaced = preg_replace(
                    '/  \/\* BEGIN: CONFIG.*?END: CONFIG \*\//s',
                    $configBlock,
                    $js
                );
                if ($replaced === null) {
                    $this->warn('⚠️  CONFIG block not found in jwt-generator.js — skipping widget sync.');
                } else {
                    file_put_contents($jsPath, $replaced);
                }
            }
        }

        return Artisan::call('scribe:inject-jwt', [], $this->output);
    }
}
