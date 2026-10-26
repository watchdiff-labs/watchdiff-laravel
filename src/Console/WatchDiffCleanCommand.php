<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Console\ConfigFile;
use WatchDiff\Core\WatchDiff;

final class WatchDiffCleanCommand extends Command
{
    protected $signature = 'watchdiff:clean {--config=} {--yes}';

    protected $description = 'Delete stored data for watchers no longer in the config file.';

    public function handle(WatchDiff $watchDiff): int
    {
        $configPath     = $this->option('config') ?? config('watchdiff.config_path');
        $configuredKeys = array_map(
            static fn ($watcher) => $watcher->key(),
            ConfigFile::load($configPath),
        );

        $orphans = array_diff($watchDiff->storedKeys(), $configuredKeys);

        if ([] === $orphans) {
            $this->info('Nothing to clean.');

            return self::SUCCESS;
        }

        if (!$this->option('yes')) {
            $this->comment(sprintf('%d orphan watcher(s) will be deleted:', \count($orphans)));
            foreach ($orphans as $orphan) {
                $this->line(' - '.$orphan);
            }

            if (!$this->input->isInteractive()) {
                $this->error('Refusing to delete without confirmation in non-interactive mode. Pass --yes to proceed.');

                return self::FAILURE;
            }

            if (!$this->confirm('Proceed?', false)) {
                $this->info('Aborted, nothing was deleted.');

                return self::SUCCESS;
            }
        }

        foreach ($orphans as $orphan) {
            $watchDiff->clear($orphan);
            $this->line(sprintf('Removed orphan data for "%s".', $orphan));
        }

        return self::SUCCESS;
    }
}
