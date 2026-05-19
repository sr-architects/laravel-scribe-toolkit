<?php

namespace SrArchitects\ScribeToolkit;

use Illuminate\Support\ServiceProvider;

class ScribeToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/openapi.php', 'openapi');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\OpenApiPublish::class,
                Commands\OpenApiGenerate::class,
                Commands\OpenApiExport::class,
                Commands\OpenApiInjectEnvironments::class,
                Commands\OpenApiInjectJwtTools::class,
                Commands\OpenApiAddApidogStatus::class,
                Commands\OpenApiInjectStatusBadge::class,
                Commands\OpenApiAddScalarStability::class,
                Commands\ScribeAddApiDogStatus::class,
                Commands\ScribeAddScalarStability::class,
                Commands\ScribeInjectJwtGenerator::class,
                Commands\ScribeInjectReport::class,
                Commands\ScribeInjectStatusBadge::class,
                Commands\GenerateAiContext::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/openapi.php' => config_path('openapi.php'),
            ], 'scribe-toolkit-config');

            $this->publishes([
                __DIR__.'/../resources/assets' => resource_path('scribe/assets'),
            ], 'scribe-toolkit-assets');
        }

        $this->autoInjectStrategiesIntoScribe();
    }

    protected function autoInjectStrategiesIntoScribe(): void
    {
        $scribeConfig = config('scribe', []);

        if (empty($scribeConfig)) {
            return;
        }

        $scribeConfig['strategies']['metadata'] = array_merge(
            $scribeConfig['strategies']['metadata'] ?? [],
            [Strategies\NoApiKeyExtractor::class]
        );

        $scribeConfig['strategies']['headers'] = array_merge(
            $scribeConfig['strategies']['headers'] ?? [],
            [
                Strategies\OptionalHeaderExtractor::class,
                Strategies\OptionalHeaderMetaExtractor::class,
            ]
        );

        config(['scribe' => $scribeConfig]);
    }
}
