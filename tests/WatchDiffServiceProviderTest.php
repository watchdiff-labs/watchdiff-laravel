<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests;

use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\Db\DbWatcher;
use WatchDiff\Core\Store\JsonStore;
use WatchDiff\Core\Store\SqliteStore;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\WatchDiffServiceProvider;

final class WatchDiffServiceProviderTest extends TestCase
{
    public function testBindsAJsonStoreByDefault(): void
    {
        self::assertInstanceOf(JsonStore::class, $this->app->make(StoreInterface::class));
    }

    public function testBindsASqliteStoreWhenConfigured(): void
    {
        if (!\in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is not installed in this environment.');
        }

        $this->app['config']->set('watchdiff.store.driver', 'sqlite');
        $this->app['config']->set('watchdiff.store.sqlite_path', sys_get_temp_dir().'/watchdiff-laravel-test-'.bin2hex(random_bytes(8)).'.db');

        self::assertInstanceOf(SqliteStore::class, $this->app->make(StoreInterface::class));
    }

    public function testWatchDiffIsASingleton(): void
    {
        self::assertSame($this->app->make(WatchDiff::class), $this->app->make(WatchDiff::class));
    }

    public function testDbWatcherIsRegisteredAsASingleton(): void
    {
        self::assertInstanceOf(DbWatcher::class, $this->app->make(DbWatcher::class));
        self::assertSame($this->app->make(DbWatcher::class), $this->app->make(DbWatcher::class));
    }

    public function testDbWatcherStillResolvesWhenAnOlderPublishedConfigIsMissingDbJsonDirectory(): void
    {
        // mergeConfigFrom() only merges top-level keys - simulate an app
        // that published config/watchdiff.php before db_json_directory
        // existed, whose 'store' array doesn't have that key at all.
        $store = $this->app['config']->get('watchdiff.store');
        unset($store['db_json_directory']);
        $this->app['config']->set('watchdiff.store', $store);

        self::assertInstanceOf(DbWatcher::class, $this->app->make(DbWatcher::class));
    }

    public function testConfigIsPublishable(): void
    {
        $paths = WatchDiffServiceProvider::pathsToPublish(WatchDiffServiceProvider::class, 'watchdiff-config');

        self::assertNotEmpty($paths);
    }
}
