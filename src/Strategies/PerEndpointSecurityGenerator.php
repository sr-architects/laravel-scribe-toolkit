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
    /**
     * @param  array<string, mixed>  $root
     * @return array<string, mixed>
     */
    public function root(array $root, array $groupedEndpoints): array
    {
        unset($root['security']);

        return $root;
    }

    /**
     * @param  array<string, mixed>  $pathItem
     * @return array<string, mixed>
     */
    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        $noApiKey      = ! empty($endpoint->metadata->custom['no_api_key']);
        $authenticated = (bool) $endpoint->metadata->authenticated;
        /** @var array<string> $optionalHeaders */
        $optionalHeaders = $endpoint->metadata->custom['optional_headers'] ?? [];

        return $this->applySecurityTo($pathItem, $noApiKey, $authenticated, $optionalHeaders);
    }

    /**
     * Core security-injection logic — extracted for unit testability.
     *
     * @param  array<string, mixed>  $pathItem
     * @param  array<string>         $optionalHeaders  lowercase header names that should be required:false
     * @return array<string, mixed>
     */
    public function applySecurityTo(
        array $pathItem,
        bool $noApiKey,
        bool $authenticated,
        array $optionalHeaders = []
    ): array {
        $apiKey = (string) config('openapi.security.apiKeyScheme', 'API_KEY');
        $jwt    = (string) config('openapi.security.jwtScheme', 'JWT_BEARER_TOKEN');

        if ($noApiKey) {
            $pathItem['security'] = [];
        } elseif ($authenticated) {
            $pathItem['security'] = [[$apiKey => [], $jwt => []]];
        } else {
            $pathItem['security'] = [[$apiKey => []]];
        }

        $rawParameters = $pathItem['parameters'] ?? null;
        if (! empty($rawParameters) && is_array($rawParameters)) {
            $processed = [];
            foreach ($rawParameters as $param) {
                if (
                    is_array($param)
                    && isset($param['in'])
                    && $param['in'] === 'header'
                    && ! array_key_exists('required', $param)
                ) {
                    $param['required'] = ! in_array(
                        strtolower(isset($param['name']) ? (string) $param['name'] : ''),
                        $optionalHeaders,
                        true
                    );
                }
                $processed[] = $param;
            }
            $pathItem['parameters'] = $processed;
        }

        return $pathItem;
    }
}
