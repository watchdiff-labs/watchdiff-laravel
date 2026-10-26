<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffCleanCommandTest extends TestCase
{
    public function testRemovesOrphanDataNotInTheConfigFileWithYes(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode(['watchers' => []]));

        $httpClient = new Client();
        $psr17      = new Psr17Factory();
        $store      = $this->app->make(StoreInterface::class);
        $watchDiff  = new WatchDiff($store, $httpClient, $psr17, $psr17);
        $this->app->singleton(WatchDiff::class, fn () => $watchDiff);

        $store->setState('https://orphan.test', ['checksCount' => 1]);

        $this->artisan('watchdiff:clean', ['--yes' => true])
            ->expectsOutputToContain('Removed orphan data for "https://orphan.test"')
            ->assertExitCode(0);

        self::assertSame([], $watchDiff->storedKeys());
    }

    public function testNothingToCleanWhenNoOrphansExist(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode(['watchers' => []]));

        $this->artisan('watchdiff:clean')
            ->expectsOutputToContain('Nothing to clean')
            ->assertExitCode(0);
    }
}
