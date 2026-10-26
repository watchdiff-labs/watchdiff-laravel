<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\WatchDiff;

final class WatchDiffExportCommand extends Command
{
    protected $signature = 'watchdiff:export {url} {--format=json} {--type=reports} {--limit=} {--output=}';

    protected $description = 'Export stored reports or snapshots for a URL as CSV or JSON.';

    public function handle(WatchDiff $watchDiff): int
    {
        $key      = (new WatchConfig((string) $this->argument('url')))->key();
        $format   = (string) $this->option('format');
        $type     = (string) $this->option('type');
        $limit    = $this->option('limit');
        $limitInt = null !== $limit ? (int) $limit : null;

        $content = match ([$type, $format]) {
            ['reports', 'csv']    => $watchDiff->exportReportsCsv($key, $limitInt),
            ['snapshots', 'csv']  => $watchDiff->exportSnapshotsCsv($key, $limitInt),
            ['snapshots', 'json'] => $watchDiff->exportSnapshotsJson($key, $limitInt),
            default               => $watchDiff->exportReportsJson($key, $limitInt),
        };

        $outputPath = $this->option('output');
        if (null !== $outputPath) {
            file_put_contents((string) $outputPath, $content."\n");
            $this->info(sprintf('Wrote %s.', $outputPath));

            return self::SUCCESS;
        }

        $this->line($content);

        return self::SUCCESS;
    }
}
