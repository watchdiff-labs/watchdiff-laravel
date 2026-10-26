<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use WatchDiff\Laravel\WatchDiffServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [WatchDiffServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $storageDir = sys_get_temp_dir().'/watchdiff-laravel-test-'.bin2hex(random_bytes(8));

        $app['config']->set('watchdiff.config_path', $storageDir.'/watchdiff.config.json');
        $app['config']->set('watchdiff.store.driver', 'json');
        $app['config']->set('watchdiff.store.json_directory', $storageDir.'/store');
        $app['config']->set('watchdiff.store.db_json_directory', $storageDir.'/store-db');
    }
}
