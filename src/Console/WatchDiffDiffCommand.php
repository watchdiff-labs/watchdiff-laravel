<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\WatchDiff;

final class WatchDiffDiffCommand extends Command
{
    protected $signature = 'watchdiff:diff {url}';

    protected $description = 'Print the last stored diff for a URL.';

    public function handle(WatchDiff $watchDiff): int
    {
        $url     = (string) $this->argument('url');
        $reports = $watchDiff->reports((new WatchConfig($url))->key(), 1);

        if ([] === $reports) {
            $this->info('No stored diff for this URL yet.');

            return self::SUCCESS;
        }

        foreach ($reports[0]->changes as $change) {
            $this->line(sprintf('[%s] %s -> %s', $change->kind->value, $change->before ?? '-', $change->after ?? '-'));
        }

        return self::SUCCESS;
    }
}
