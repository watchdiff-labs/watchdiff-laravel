<?php

declare(strict_types=1);

return [
    // Where watchers are declared, as understood by WatchDiff\Core\Console\ConfigFile.
    'config_path' => base_path('watchdiff.config.json'),

    'store' => [
        // 'json' or 'sqlite'
        'driver' => env('WATCHDIFF_STORE_DRIVER', 'json'),
        'json_directory' => storage_path('app/watchdiff'),
        'sqlite_path' => storage_path('app/watchdiff.db'),

        // Database-table watchers (DbWatcher) intentionally never share
        // storage with the URL/file/cert watchers above - `watchdiff:clean`
        // deletes any stored key that isn't a configured URL/file/cert
        // watcher, so a shared directory would make DbWatcher's own
        // watchers look like orphans.
        'db_json_directory' => storage_path('app/watchdiff-db'),
    ],
];
