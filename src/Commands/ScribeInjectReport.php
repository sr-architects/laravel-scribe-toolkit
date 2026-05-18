<?php

namespace SrArchitects\ScribeToolkit\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class ScribeInjectReport extends Command
{
    protected $signature = 'scribe:inject-report
                            {--report=api_validation_report.md : Path to the report file relative to project root}
                            {--yaml=storage/app/scribe/openapi.yaml : Path to the OpenAPI YAML file}';

    protected $description = 'Inject api_validation_report.md into the info.description field of the OpenAPI YAML spec';

    public function handle(): int
    {
        $reportPath = base_path($this->option('report'));
        $yamlPath = base_path($this->option('yaml'));

        if (! file_exists($yamlPath)) {
            $this->error("OpenAPI YAML not found at: {$yamlPath}");
            $this->error('Run `php artisan scribe:generate` first.');
            return self::FAILURE;
        }

        if (! file_exists($reportPath)) {
            $this->error("Report file not found at: {$reportPath}");
            $this->error('Run `/orchestrate-api` or `/reporter` to generate it.');
            return self::FAILURE;
        }

        $reportContent = file_get_contents($reportPath);
        if ($reportContent === false || trim($reportContent) === '') {
            $this->error('Report file is empty or unreadable.');
            return self::FAILURE;
        }

        $spec = Yaml::parseFile($yamlPath);
        if (! isset($spec['info'])) {
            $this->error('OpenAPI spec is missing the `info` section.');
            return self::FAILURE;
        }

        $existingDescription = $spec['info']['description'] ?? '';
        $beginMarker = '<!-- BEGIN: API Validation Report (auto-injected by scribe:inject-report) -->';
        $endMarker = '<!-- END: API Validation Report -->';

        if (str_contains($existingDescription, '<!-- BEGIN: API Validation Report')) {
            $existingDescription = preg_replace(
                '/\s*---\s*\n*' . preg_quote($beginMarker, '/') . '.*?' . preg_quote($endMarker, '/') . '/s',
                '',
                $existingDescription
            );
            $existingDescription = preg_replace(
                '/' . preg_quote($beginMarker, '/') . '.*?' . preg_quote($endMarker, '/') . '/s',
                '',
                $existingDescription
            );
            $existingDescription = trim($existingDescription);
        }

        $finalDescription = $existingDescription;
        if ($finalDescription !== '') {
            $finalDescription .= "\n\n---\n\n";
        }
        $finalDescription .= $beginMarker . "\n" . $reportContent . "\n" . $endMarker;

        $spec['info']['description'] = $finalDescription;

        file_put_contents(
            $yamlPath,
            Yaml::dump($spec, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $reportSize = strlen($reportContent);
        $this->info("Injected api_validation_report.md ({$reportSize} bytes) into info.description.");
        $this->info("Output: {$yamlPath}");

        return self::SUCCESS;
    }
}
