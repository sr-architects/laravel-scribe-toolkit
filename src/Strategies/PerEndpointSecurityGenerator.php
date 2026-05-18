<?php

namespace SrArchitects\ScribeToolkit\Strategies;

use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;

/**
 * Replaces Scribe's global-security approach with per-operation security.
 *
 * Security matrix:
 *   - @noApiKey:                   []
 *   - @unauthenticated / default:  [{<apiKeyScheme>: []}]
 *   - @authenticated:              [{<apiKeyScheme>: [], <jwtScheme>: []}]
 *
 * Scheme names are read from config/openapi.php:
 *   openapi.security.apiKeyScheme  (default: 'API_KEY')
 *   openapi.security.jwtScheme     (default: 'JWT_BEARER_TOKEN')
 */
class PerEndpointSecurityGenerator extends OpenApiGenerator
{
    public function root(array $root, array $groupedEndpoints): array
    {
        unset($root['security']);

        return $root;
    }

    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        $apiKey = config('openapi.security.apiKeyScheme', 'API_KEY');
        $jwt    = config('openapi.security.jwtScheme', 'JWT_BEARER_TOKEN');

        if (! empty($endpoint->metadata->custom['no_api_key'])) {
            $pathItem['security'] = [];
        } elseif ($endpoint->metadata->authenticated) {
            $pathItem['security'] = [[$apiKey => [], $jwt => []]];
        } else {
            $pathItem['security'] = [[$apiKey => []]];
        }

        if (! empty($pathItem['parameters'])) {
            $optionalHeaders = array_map(
                'strtolower',
                $endpoint->metadata->custom['optional_headers'] ?? []
            );

            $pathItem['parameters'] = array_map(function (array $param) use ($optionalHeaders): array {
                if (($param['in'] ?? '') === 'header' && ! array_key_exists('required', $param)) {
                    $param['required'] = ! in_array(strtolower($param['name'] ?? ''), $optionalHeaders, true);
                }
                return $param;
            }, $pathItem['parameters']);
        }

        return $pathItem;
    }
}
