<?php

namespace SrArchitects\ScribeToolkit\Strategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

/**
 * Reads @custom-headerOptional tags and stores the header names in metadata.custom
 * so PerEndpointSecurityGenerator can set required: false for those headers.
 */
class OptionalHeaderMetaExtractor extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        $headers = OptionalHeaderExtractor::parseOptionalHeaders($endpointData);

        if (empty($headers)) {
            return null;
        }

        return ['custom' => ['optional_headers' => array_keys($headers)]];
    }
}
