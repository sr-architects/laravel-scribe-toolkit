<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Symfony\Component\Yaml\Yaml;

class ScribeAddApiDogStatus extends Command
{
    protected $signature = 'scribe:add-apidog-status';
    protected $description = 'Inject x-apidog-status and @custom-desc into the generated OpenAPI YAML from controller docblocks';

    private const ALLOWED_STATUSES = [
        'designing',
        'pending',
        'developing',
        'integrating',
        'testing',
        'tested',
        'released',
        'deprecated',
        'exception',
        'obsolete',
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

        $statusMap      = $this->buildStatusMap();
        $descriptionMap = $this->buildDescriptionMap();
        $spec = Yaml::parseFile($yamlPath);

        $normalizedMap = [];
        foreach ($statusMap as $key => $status) {
            [$httpMethod, $routePath] = explode(':', $key, 2);
            $normalizedMap[$httpMethod . ':' . $this->normalizePath($routePath)] = $status;
        }

        $normalizedDescMap = [];
        foreach ($descriptionMap as $key => $desc) {
            [$httpMethod, $routePath] = explode(':', $key, 2);
            $normalizedDescMap[$httpMethod . ':' . $this->normalizePath($routePath)] = $desc;
        }

        if (empty($spec['paths'])) {
            $this->warn('No paths found in OpenAPI spec.');
            return self::SUCCESS;
        }

        $rows = [];
        $modified = 0;
        foreach ($spec['paths'] as $path => &$pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (! isset($pathItem[$method])) {
                    continue;
                }

                $key = strtoupper($method) . ':' . $this->normalizePath($path);
                $status = $normalizedMap[$key] ?? 'pending';
                $pathItem[$method]['x-apidog-status'] = $status;

                $customDesc = $normalizedDescMap[$key] ?? '';
                $desc = $customDesc !== '' ? $customDesc : (string) ($pathItem[$method]['description'] ?? '');
                $desc = (string) preg_replace('/^Status:\s+\S+[ \t]*/i', '', $desc);
                $desc = (string) preg_replace('/^!\[API Status\]\(https:\/\/img\.shields\.io\/badge[^\)]+\)\n*/i', '', $desc);
                $desc = trim($desc);

                if ($desc !== '') {
                    $pathItem[$method]['description'] = $desc;
                } else {
                    unset($pathItem[$method]['description']);
                }

                $rows[] = [strtoupper($method), $path, $status];
                $modified++;
            }
        }
        unset($pathItem);

        file_put_contents($yamlPath, Yaml::dump($spec, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $this->table(['Method', 'Path', 'x-apidog-status'], $rows);
        $this->info("Injected x-apidog-status into {$modified} operations.");

        return self::SUCCESS;
    }

    private function parseDescriptionTag(string $docBlock): string
    {
        $lines = explode("\n", $docBlock);
        $capturing = false;
        $collected = [];

        foreach ($lines as $line) {
            $stripped = trim((string) preg_replace('/^\s*\*\s?/', '', $line));

            if (preg_match('/^@custom-desc[ 	]+(.*)/i', $stripped, $m)) {
                $capturing = true;
                $collected[] = trim($m[1]);
                continue;
            }

            if ($capturing) {
                if (preg_match('/^@\w/', $stripped)) {
                    break;
                }
                $collected[] = $stripped;
            }
        }

        return trim(implode("\n", $collected));
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

    private function buildDescriptionMap(): array
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

                $desc = $this->parseDescriptionTag($docBlock);
                if ($desc === '') {
                    continue;
                }

                $path = '/' . ltrim($this->stripRoutePrefix($route->uri()), '/');

                foreach ($route->methods() as $httpMethod) {
                    if (in_array($httpMethod, ['HEAD', 'OPTIONS'])) {
                        continue;
                    }
                    $map[strtoupper($httpMethod) . ':' . $path] = $desc;
                }
            } catch (\ReflectionException) {
                continue;
            }
        }

        return $map;
    }

    private function buildStatusMap(): array
    {
        $map = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction();

            if (! isset($action['controller'])) {
                continue;
            }

            $parts = explode('@', $action['controller']);
            $class = $parts[0];
            $method = $parts[1] ?? '__invoke';

            if (! class_exists($class)) {
                continue;
            }

            try {
                $reflection = new ReflectionMethod($class, $method);
                $docBlock = $reflection->getDocComment();

                $status = 'pending';
                if ($docBlock && preg_match('/@custom-status\s+(\S+)/', $docBlock, $matches)) {
                    $value = trim($matches[1]);
                    $status = in_array($value, self::ALLOWED_STATUSES) ? $value : 'pending';
                }

                $path = '/' . ltrim($this->stripRoutePrefix($route->uri()), '/');

                foreach ($route->methods() as $httpMethod) {
                    if (in_array($httpMethod, ['HEAD', 'OPTIONS'])) {
                        continue;
                    }
                    $map[strtoupper($httpMethod) . ':' . $path] = $status;
                }
            } catch (\ReflectionException) {
                continue;
            }
        }

        return $map;
    }
}
