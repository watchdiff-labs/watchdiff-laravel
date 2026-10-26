<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Console\ConfigFile;
use WatchDiff\Core\WatchDiff;

final class WatchDiffResumeCommand extends Command
{
    protected $signature = 'watchdiff:resume {tagOrId} {--config=}';

    protected $description = 'Resume a watcher by id, URL, or tag.';

    public function handle(WatchDiff $watchDiff): int
    {
        $configPath = $this->option('config') ?? config('watchdiff.config_path');

        foreach (ConfigFile::load($configPath) as $watcher) {
            $watchDiff->watch($watcher);
        }

        $tagOrId = (string) $this->argument('tagOrId');
        $watchDiff->resume($tagOrId);
        $this->info(sprintf('Resumed watcher(s) matching "%s".', $tagOrId));

        return self::SUCCESS;
    }
}
