<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\WatchDiff;

final class WatchDiffSnapshotCommand extends Command
{
    protected $signature = 'watchdiff:snapshot {url}';

    protected $description = 'Print the latest stored snapshot for a URL.';

    public function handle(WatchDiff $watchDiff): int
    {
        $url       = (string) $this->argument('url');
        $snapshots = $watchDiff->history((new WatchConfig($url))->key(), 1);

        if ([] === $snapshots) {
            $this->info('No snapshot stored for this URL yet.');

            return self::SUCCESS;
        }

        $this->line($snapshots[0]->content);

        return self::SUCCESS;
    }
}
