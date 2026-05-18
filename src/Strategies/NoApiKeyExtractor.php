<?php

namespace SrArchitects\ScribeToolkit\Strategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\RouteDocBlocker;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

/**
 * Reads @custom-noapikey from a route/controller docblock and stores it in metadata.custom.
 */
class NoApiKeyExtractor extends Strategy
{
    /**
     * @param  array<mixed>  $routeRules
     * @return array<string, mixed>|null
     */
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        $route = $endpointData->route;
        if ($route === null) {
            return null;
        }
        $docBlock = RouteDocBlocker::getDocBlocksFromRoute($route)['method'];

        foreach ($docBlock->getTags() as $tag) {
            if (mb_strtolower($tag->getName()) === 'custom-noapikey') {
                return ['custom' => ['no_api_key' => true]];
            }
        }

        return null;
    }
}
