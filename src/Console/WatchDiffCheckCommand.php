<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\Enum\DiffMode;
use WatchDiff\Core\WatchDiff;

final class WatchDiffCheckCommand extends Command
{
    protected $signature = 'watchdiff:check {url} {--target=} {--diff-mode=line} {--dry-run}';

    protected $description = 'Run a single one-off check against a URL.';

    public function handle(WatchDiff $watchDiff): int
    {
        $config = new WatchConfig(
            url: $this->argument('url'),
            target: $this->option('target'),
            diffMode: DiffMode::from($this->option('diff-mode')),
            dryRun: (bool) $this->option('dry-run'),
        );

        $watchDiff->watch($config);
        $report = $watchDiff->checkOnce($config->key());

        if (null === $report) {
            $this->info('First check - snapshot saved, nothing to compare yet.');

            return self::SUCCESS;
        }

        if (!$report->hasChanges()) {
            $this->info('No changes detected.');

            return self::SUCCESS;
        }

        foreach ($report->changes as $change) {
            $this->line(sprintf('[%s] %s -> %s', $change->kind->value, $change->before ?? '-', $change->after ?? '-'));
        }

        return self::SUCCESS;
    }
}
