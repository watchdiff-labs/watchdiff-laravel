<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\WatchDiff;

final class WatchDiffClearCommand extends Command
{
    protected $signature = 'watchdiff:clear {url}';

    protected $description = 'Delete all stored snapshots, reports and state for a single URL.';

    public function handle(WatchDiff $watchDiff): int
    {
        $key = (new WatchConfig((string) $this->argument('url')))->key();

        $watchDiff->clear($key);
        $this->info(sprintf('Cleared stored data for "%s".', $key));

        return self::SUCCESS;
    }
}
