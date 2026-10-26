<?php

declare(strict_types=1);

namespace WatchDiff\Laravel;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Support\ServiceProvider;
use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\Db\DbWatcher;
use WatchDiff\Core\Store\JsonStore;
use WatchDiff\Core\Store\SqliteStore;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Console\WatchDiffCheckCommand;
use WatchDiff\Laravel\Console\WatchDiffCleanCommand;
use WatchDiff\Laravel\Console\WatchDiffClearCommand;
use WatchDiff\Laravel\Console\WatchDiffCompareCommand;
use WatchDiff\Laravel\Console\WatchDiffDbCommand;
use WatchDiff\Laravel\Console\WatchDiffDiffCommand;
use WatchDiff\Laravel\Console\WatchDiffExportCommand;
use WatchDiff\Laravel\Console\WatchDiffHistoryCommand;
use WatchDiff\Laravel\Console\WatchDiffInitCommand;
use WatchDiff\Laravel\Console\WatchDiffListCommand;
use WatchDiff\Laravel\Console\WatchDiffPauseCommand;
use WatchDiff\Laravel\Console\WatchDiffReportsCommand;
use WatchDiff\Laravel\Console\WatchDiffResumeCommand;
use WatchDiff\Laravel\Console\WatchDiffSnapshotCommand;
use WatchDiff\Laravel\Console\WatchDiffTickCommand;
use WatchDiff\Laravel\Console\WatchDiffValidateCommand;

final class WatchDiffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/watchdiff.php', 'watchdiff');

        $this->app->singleton(StoreInterface::class, function () {
            $config = config('watchdiff.store');

            return 'sqlite' === $config['driver']
                ? new SqliteStore($config['sqlite_path'])
                : new JsonStore($config['json_directory']);
        });

        $this->app->singleton(WatchDiff::class, fn () => new WatchDiff(
            $this->app->make(StoreInterface::class),
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        ));

        $this->app->singleton(DbWatcher::class, fn () => new DbWatcher(
            new JsonStore(config('watchdiff.store.db_json_directory') ?? storage_path('app/watchdiff-db')),
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/watchdiff.php' => config_path('watchdiff.php'),
            ], 'watchdiff-config');

            $this->commands([
                WatchDiffInitCommand::class,
                WatchDiffTickCommand::class,
                WatchDiffCheckCommand::class,
                WatchDiffValidateCommand::class,
                WatchDiffListCommand::class,
                WatchDiffPauseCommand::class,
                WatchDiffResumeCommand::class,
                WatchDiffCleanCommand::class,
                WatchDiffDiffCommand::class,
                WatchDiffHistoryCommand::class,
                WatchDiffSnapshotCommand::class,
                WatchDiffClearCommand::class,
                WatchDiffReportsCommand::class,
                WatchDiffExportCommand::class,
                WatchDiffCompareCommand::class,
                WatchDiffDbCommand::class,
            ]);
        }
    }
}
