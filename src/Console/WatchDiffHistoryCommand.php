<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\WatchDiff;

final class WatchDiffHistoryCommand extends Command
{
    protected $signature = 'watchdiff:history {url} {--limit=10}';

    protected $description = 'List stored snapshots for a URL.';

    public function handle(WatchDiff $watchDiff): int
    {
        $url       = (string) $this->argument('url');
        $snapshots = $watchDiff->history((new WatchConfig($url))->key(), (int) $this->option('limit'));

        if ([] === $snapshots) {
            $this->info('No snapshots stored for this URL yet.');

            return self::SUCCESS;
        }

        foreach ($snapshots as $snapshot) {
            $this->line(sprintf('%s - status %d', $snapshot->capturedAt->format(DATE_ATOM), $snapshot->statusCode));
        }

        return self::SUCCESS;
    }
}
