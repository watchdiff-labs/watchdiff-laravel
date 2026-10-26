<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use WatchDiff\Core\Console\ConfigFile;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Events\WatcherChanged;

final class WatchDiffTickCommand extends Command
{
    protected $signature = 'watchdiff:tick {--config=}';

    protected $description = 'Check every due watcher and report changes. Intended to run from the scheduler.';

    public function handle(WatchDiff $watchDiff, Dispatcher $events): int
    {
        $configPath = $this->option('config') ?? config('watchdiff.config_path');

        foreach (ConfigFile::load($configPath) as $watcher) {
            $watchDiff->watch($watcher);
        }

        $reports = $watchDiff->tick();

        if ([] === $reports) {
            $this->info('No changes detected.');

            return self::SUCCESS;
        }

        foreach ($reports as $report) {
            $this->line(sprintf('<comment>%s</comment>: %d change(s)', $report->label, \count($report->changes)));
            $events->dispatch(new WatcherChanged($report));
        }

        return self::SUCCESS;
    }
}
