<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Console\ConfigFile;
use WatchDiff\Core\WatchDiff;

final class WatchDiffPauseCommand extends Command
{
    protected $signature = 'watchdiff:pause {tagOrId} {--config=}';

    protected $description = 'Pause a watcher by id, URL, or tag.';

    public function handle(WatchDiff $watchDiff): int
    {
        $configPath = $this->option('config') ?? config('watchdiff.config_path');

        foreach (ConfigFile::load($configPath) as $watcher) {
            $watchDiff->watch($watcher);
        }

        $tagOrId = (string) $this->argument('tagOrId');
        $watchDiff->pause($tagOrId);
        $this->info(sprintf('Paused watcher(s) matching "%s".', $tagOrId));

        return self::SUCCESS;
    }
}
