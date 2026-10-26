<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffListCommandTest extends TestCase
{
    public function testListsConfiguredWatchersWithTheirStatus(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode([
            'watchers' => [['url' => 'https://example.test/page', 'label' => 'Example page']],
        ]));

        $this->artisan('watchdiff:list')
            ->expectsOutputToContain('Example page')
            ->assertExitCode(0);
    }

    public function testNoWatchersConfigured(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode(['watchers' => []]));

        $this->artisan('watchdiff:list')
            ->expectsOutputToContain('No watchers configured')
            ->assertExitCode(0);
    }
}
