<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Console\ConfigFile;
use WatchDiff\Core\WatchDiff;

final class WatchDiffListCommand extends Command
{
    protected $signature = 'watchdiff:list {--config=}';

    protected $description = 'List configured watchers and their status.';

    public function handle(WatchDiff $watchDiff): int
    {
        $configPath = $this->option('config') ?? config('watchdiff.config_path');

        foreach (ConfigFile::load($configPath) as $watcher) {
            $watchDiff->watch($watcher);
        }

        $statuses = $watchDiff->status();

        if ([] === $statuses) {
            $this->info('No watchers configured.');

            return self::SUCCESS;
        }

        foreach ($statuses as $status) {
            $this->line(sprintf(
                '%s%s | target=%s | interval=%ds | checks=%d changes=%d errors=%d',
                $status->label,
                $status->paused ? ' [paused]' : '',
                $status->target ?? '(full page)',
                $status->interval,
                $status->checksCount,
                $status->changesCount,
                $status->errorsCount,
            ));
        }

        return self::SUCCESS;
    }
}
