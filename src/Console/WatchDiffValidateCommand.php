<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Console\ConfigFile;

final class WatchDiffValidateCommand extends Command
{
    protected $signature = 'watchdiff:validate {--config=}';

    protected $description = 'Validate the watchdiff config file.';

    public function handle(): int
    {
        $configPath = $this->option('config') ?? config('watchdiff.config_path');
        $errors     = ConfigFile::validate($configPath);

        if ([] === $errors) {
            $this->info('Config is valid.');

            return self::SUCCESS;
        }

        foreach ($errors as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }
}
