<?php

namespace SrArchitects\ScribeToolkit\Strategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

class StripApiPrefix extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        // Keep /api prefix on local — no ingress stripping in local dev
        if (config('app.env') === 'local') {
            return null;
        }

        if (str_starts_with($endpointData->uri, 'api/')) {
            $endpointData->uri = substr($endpointData->uri, 4);
        }

        return null;
    }
}
