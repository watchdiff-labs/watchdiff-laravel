<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\WatchDiff;

final class WatchDiffCompareCommand extends Command
{
    protected $signature = 'watchdiff:compare {urlA} {urlB} {--target=}';

    protected $description = 'Diff two arbitrary URLs in a single command.';

    public function handle(WatchDiff $watchDiff): int
    {
        $report = $watchDiff->compareUrls(
            (string) $this->argument('urlA'),
            (string) $this->argument('urlB'),
            $this->option('target'),
        );

        if (!$report->hasChanges()) {
            $this->info('No differences found.');

            return self::SUCCESS;
        }

        foreach ($report->changes as $change) {
            $this->line(sprintf('[%s] %s -> %s', $change->kind->value, $change->before ?? '-', $change->after ?? '-'));
        }

        return self::SUCCESS;
    }
}
