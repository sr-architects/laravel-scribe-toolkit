<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Symfony\Component\Yaml\Yaml;

class ScribeAddScalarStability extends Command
{
    protected $signature = 'scribe:add-scalar-stability';
    protected $description = 'Inject x-stability into operations that have a @custom-stability docblock annotation';

    private const ALLOWED_STABILITIES = [
        'stable',
        'experimental',
        'deprecated',
    ];

    public function handle(): int
    {
        $type = config('scribe.type', 'laravel');
        if ($type === 'static') {
            $outputPath = (string) config('scribe.static.output_path', 'public/docs');
            $yamlPath = base_path(rtrim($outputPath, '/') . '/openapi.yaml');
        } else {
            $yamlPath = storage_path('app/scribe/openapi.yaml');
        }

        if (! file_exists($yamlPath)) {
            $this->error('openapi.yaml not found. Run php artisan scribe:generate first.');
            return self::FAILURE;
        }

        $stabilityMap = $this->buildStabilityMap();

        if (empty($stabilityMap)) {
            $this->line('⏭  No @custom-stability annotations found — skipped');
            return self::SUCCESS;
        }

        $spec = Yaml::parseFile($yamlPath);

        if (empty($spec['paths'])) {
            $this->warn('No paths found in OpenAPI spec.');
            return self::SUCCESS;
        }

        $rows     = [];
        $injected = 0;

        $normalizedMap = [];
        foreach ($stabilityMap as $key => $stability) {
            [$httpMethod, $routePath] = explode(':', $key, 2);
            $normalizedMap[$httpMethod . ':' . $this->normalizePath($routePath)] = $stability;
        }

        foreach ($spec['paths'] as $path => &$pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (! isset($pathItem[$method])) {
                    continue;
                }

                $key = strtoupper($method) . ':' . $this->normalizePath($path);

                if (! isset($normalizedMap[$key])) {
                    continue;
                }

                $pathItem[$method]['x-stability'] = $normalizedMap[$key];
                $rows[] = [strtoupper($method), $path, $normalizedMap[$key]];
                $injected++;
            }
        }
        unset($pathItem);

        file_put_contents($yamlPath, Yaml::dump($spec, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        if ($injected === 0) {
            $this->line('⏭  No matching paths in spec — nothing injected');
            return self::SUCCESS;
        }

        $this->table(['Method', 'Path', 'x-stability'], $rows);
        $this->info("Injected x-stability into {$injected} operation(s).");

        return self::SUCCESS;
    }

    private function buildStabilityMap(): array
    {
        $map = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction();

            if (! isset($action['controller'])) {
                continue;
            }

            $parts  = explode('@', $action['controller']);
            $class  = $parts[0];
            $method = $parts[1] ?? '__invoke';

            if (! class_exists($class)) {
                continue;
            }

            try {
                $reflection = new ReflectionMethod($class, $method);
                $docBlock   = $reflection->getDocComment();

                if (! $docBlock) {
                    continue;
                }

                if (! preg_match('/@custom-stability\s+(\S+)/', $docBlock, $matches)) {
                    continue;
                }

                $value = trim($matches[1]);

                if (! in_array($value, self::ALLOWED_STABILITIES)) {
                    continue;
                }

                $path = '/' . ltrim($this->stripRoutePrefix($route->uri()), '/');

                foreach ($route->methods() as $httpMethod) {
                    if (in_array($httpMethod, ['HEAD', 'OPTIONS'])) {
                        continue;
                    }
                    $map[strtoupper($httpMethod) . ':' . $path] = $value;
                }
            } catch (\ReflectionException) {
                continue;
            }
        }

        return $map;
    }

    private function normalizePath(string $path): string
    {
        return preg_replace('/\{[^}]+\}/', '{param}', $path);
    }

    private function stripRoutePrefix(string $uri): string
    {
        $patterns = (array) config('scribe.routes.0.match.prefixes', []);
        foreach ($patterns as $pattern) {
            $prefix = rtrim(str_replace('*', '', $pattern), '/');
            if ($prefix !== '' && str_starts_with($uri, $prefix . '/')) {
                return substr($uri, strlen($prefix) + 1);
            }
        }

        return $uri;
    }
}
