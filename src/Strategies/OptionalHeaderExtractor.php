<?php

namespace SrArchitects\ScribeToolkit\Strategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\RouteDocBlocker;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

/**
 * Reads @custom-headerOptional from a route docblock and adds it to the headers extraction.
 *
 * Usage in docblock:
 *   @custom-headerOptional X-Some-Header example-value
 */
class OptionalHeaderExtractor extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        return self::parseOptionalHeaders($endpointData);
    }

    /**
     * @return array<string, string>|null  [header-name => example] or null if none found
     */
    public static function parseOptionalHeaders(ExtractedEndpointData $endpointData): ?array
    {
        $docBlock = RouteDocBlocker::getDocBlocksFromRoute($endpointData->route)['method'];

        $headers = [];
        foreach ($docBlock->getTags() as $tag) {
            if (mb_strtolower($tag->getName()) !== 'custom-headeroptional') {
                continue;
            }
            preg_match('/([\S]+)(.*)/', $tag->getContent(), $m);
            $name = $m[1] ?? '';
            $example = trim($m[2] ?? '');
            if ($name !== '') {
                $headers[$name] = $example !== '' ? $example : 'optional';
            }
        }

        return $headers ?: null;
    }
}
