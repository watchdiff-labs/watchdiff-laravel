<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\WatchDiff;

final class WatchDiffReportsCommand extends Command
{
    protected $signature = 'watchdiff:reports {url} {--limit=10}';

    protected $description = 'List stored change reports for a URL.';

    public function handle(WatchDiff $watchDiff): int
    {
        $url     = (string) $this->argument('url');
        $reports = $watchDiff->reports((new WatchConfig($url))->key(), (int) $this->option('limit'));

        if ([] === $reports) {
            $this->info('No stored reports for this URL yet.');

            return self::SUCCESS;
        }

        foreach ($reports as $report) {
            $this->line(sprintf('%s - %d change(s)', $report->comparedAt->format(DATE_ATOM), \count($report->changes)));
        }

        return self::SUCCESS;
    }
}
