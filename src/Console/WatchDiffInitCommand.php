<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;

final class WatchDiffInitCommand extends Command
{
    protected $signature = 'watchdiff:init {--config=}';

    protected $description = 'Create a starter watchdiff config file at the configured path.';

    public function handle(): int
    {
        $path = $this->option('config') ?? config('watchdiff.config_path');

        if (is_file($path)) {
            $this->error(sprintf('%s already exists.', $path));

            return self::FAILURE;
        }

        $template = [
            'watchers' => [
                [
                    'url'      => 'https://example.com',
                    'target'   => '.price',
                    'interval' => 300,
                    'diffMode' => 'line',
                    'webhooks' => [],
                ],
            ],
        ];

        file_put_contents($path, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $this->info(sprintf('Created %s', $path));

        return self::SUCCESS;
    }
}
