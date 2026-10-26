# watchdiff-laravel

[![Packagist Version](https://img.shields.io/packagist/v/watchdiff-labs/watchdiff-laravel)](https://packagist.org/packages/watchdiff-labs/watchdiff-laravel)
[![Tests](https://github.com/watchdiff-labs/watchdiff-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/watchdiff-labs/watchdiff-laravel/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue)](LICENSE)
[![Packagist Downloads](https://img.shields.io/packagist/dt/watchdiff-labs/watchdiff-laravel)](https://packagist.org/packages/watchdiff-labs/watchdiff-laravel)

Laravel adapter for [`watchdiff-core`](https://packagist.org/packages/watchdiff-labs/watchdiff-core):
web change monitoring (page diffs, APIs, file/SSL/DB watchers, webhooks) wired into Laravel's
service container, Artisan, and scheduler. Installing this package pulls in `watchdiff-core`
automatically - you never need to require it yourself.

- **Monitors:** web pages, JSON APIs, RSS/Atom feeds, sitemaps, local files, SSL
  certificates, and database tables.
- **Notifies via:** webhooks (Discord/Slack/Telegram/ntfy/...), callbacks in your own code,
  or an AI-generated summary of each change.
- **Also handles:** retries, cooldowns, scheduling, pausing/tagging watchers, and export.

This README covers the Laravel-specific wiring (service container, config, Artisan
commands). For the full feature list with examples, see
[`watchdiff-core`'s own README](https://github.com/watchdiff-labs/watchdiff-php#readme), or
its [Packagist page](https://packagist.org/packages/watchdiff-labs/watchdiff-core).

## Table of contents

- [Install](#install)
- [Quick start](#quick-start)
- [Configuration reference](#configuration-reference)
- [Usage in your own code](#usage-in-your-own-code)
  - [Controller](#controller)
  - [Eloquent model](#eloquent-model)
  - [Queued job](#queued-job)
- [Reacting to changes: events](#reacting-to-changes-events)
- [Database watchers](#database-watchers)
- [Scheduling](#scheduling)
- [Store drivers](#store-drivers)
- [What this package does and doesn't add](#what-this-package-does-and-doesnt-add)
- [Security](#security)
- [Commands](#commands)
  - [`watchdiff:init`](#watchdiffinit)
  - [`watchdiff:tick`](#watchdifftick)
  - [`watchdiff:check`](#watchdiffcheck)
  - [`watchdiff:validate`](#watchdiffvalidate)
  - [`watchdiff:list`](#watchdifflist)
  - [`watchdiff:pause` / `watchdiff:resume`](#watchdiffpause--watchdiffresume)
  - [`watchdiff:clean`](#watchdiffclean)
  - [`watchdiff:diff` / `watchdiff:history` / `watchdiff:snapshot` / `watchdiff:reports`](#watchdiffdiff--watchdiffhistory--watchdiffsnapshot--watchdiffreports)
  - [`watchdiff:clear`](#watchdiffclear)
  - [`watchdiff:export`](#watchdiffexport)
  - [`watchdiff:compare`](#watchdiffcompare)
  - [`watchdiff:db`](#watchdiffdb)
- [Development](#development)
- [License](#license)

## Install

```bash
composer require watchdiff-labs/watchdiff-laravel
php artisan vendor:publish --tag=watchdiff-config
```

Supported: Laravel 10, 11, 12, PHP 8.1+.

Not a PHP project? watchdiff also ships natively for Node.js and Python - same monitoring
concepts, ported to each ecosystem rather than wrapped:

```bash
npm install watchdiff-core
```

```bash
pip install watchdiff-core
```

- npm (Node.js): https://www.npmjs.com/package/watchdiff-core
- PyPI (Python): https://pypi.org/project/watchdiff-core/

The service provider binds a PSR-18 HTTP client and PSR-17 factories via
`php-http/discovery` (`watchdiff-core` itself stays HTTP-client-agnostic by design), so
install one in your app if you don't already have one:

```bash
composer require guzzlehttp/guzzle
```

## Quick start

1. Create `watchdiff.config.json` at your project root (same format as
   `watchdiff-core`'s own CLI - see its README for the full option reference):

   ```json
   {
       "watchers": [
           { "url": "https://example.com/pricing", "target": ".price", "interval": 300 }
       ]
   }
   ```

2. Run a tick manually to confirm it's wired correctly:

   ```bash
   php artisan watchdiff:tick
   ```

3. Schedule it (see [Scheduling](#scheduling)) - each watcher's own `interval` decides how
   often it actually runs, `watchdiff:tick` just checks which ones are due.

## Configuration reference

Published to `config/watchdiff.php`:

```php
return [
    'config_path' => base_path('watchdiff.config.json'),

    'store' => [
        'driver' => env('WATCHDIFF_STORE_DRIVER', 'json'), // 'json' or 'sqlite'
        'json_directory' => storage_path('app/watchdiff'),
        'sqlite_path' => storage_path('app/watchdiff.db'),
        'db_json_directory' => storage_path('app/watchdiff-db'),
    ],
];
```

| Key | Default | Meaning |
|---|---|---|
| `config_path` | `base_path('watchdiff.config.json')` | Default path read by every command below unless `--config` overrides it. |
| `store.driver` | `json` (env `WATCHDIFF_STORE_DRIVER`) | `json` (one file per watcher, human-readable) or `sqlite` (single file, better for many watchers). |
| `store.json_directory` | `storage_path('app/watchdiff')` | Used when `driver: json`. |
| `store.sqlite_path` | `storage_path('app/watchdiff.db')` | Used when `driver: sqlite`. |
| `store.db_json_directory` | `storage_path('app/watchdiff-db')` | Storage for the separate `DbWatcher` binding (see [Database watchers](#database-watchers)) - always its own directory, never shared with the main store (see why below). |

## Usage in your own code

`WatchDiff\Core\WatchDiff` and `WatchDiff\Core\Db\DbWatcher` are bound as singletons -
resolve them from the container anywhere, via constructor injection or the `app()` helper.
Every feature documented in `watchdiff-core`'s own README (diff modes, webhooks, AI
summaries, retries, flapping detection, maintenance windows, ...) is available through the
same `WatchConfig`/`DbWatchConfig` options - this package only adds the service bindings
and Artisan commands, it doesn't reimplement or restrict anything from the core library. A
few concrete examples:

### Controller

```php
use Illuminate\Http\JsonResponse;
use WatchDiff\Core\WatchDiff;

final class WatcherStatusController
{
    public function __invoke(WatchDiff $watchDiff): JsonResponse
    {
        return response()->json(array_map(
            static fn ($s) => ['label' => $s->label, 'paused' => $s->paused, 'changes' => $s->changesCount],
            $watchDiff->status(),
        ));
    }
}
```

### Eloquent model

Resolve the service from the container inside a model method - Eloquent models aren't
constructed by the container, so inject via `app()` rather than a constructor argument:

```php
use Illuminate\Database\Eloquent\Model;
use WatchDiff\Core\Db\DbWatchConfig;
use WatchDiff\Core\Db\DbDiffMode;
use WatchDiff\Core\Db\DbWatcher;

final class Product extends Model
{
    public static function watchStockLevels(): void
    {
        app(DbWatcher::class)->watch(new DbWatchConfig(
            connectionString: config('database.connections.mysql.database'),
            table: (new self())->getTable(),
            query: 'SELECT SUM(stock) FROM products',
            diffMode: DbDiffMode::Value,
        ));
    }
}
```

### Queued job

Trigger a check from a job, e.g. after importing a list of URLs to monitor:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\WatchDiff;

final class RegisterUrlWatcher implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $url, private readonly string $selector) {}

    public function handle(WatchDiff $watchDiff): void
    {
        $watchDiff->watch(new WatchConfig(url: $this->url, target: $this->selector));
    }
}
```

## Reacting to changes: events

`watchdiff:tick` fires `WatchDiff\Laravel\Events\WatcherChanged` (carrying the `DiffReport`)
for every watcher that changed, through Laravel's own event dispatcher - this is the
integration point for notifications, queued jobs, logging, or anything else, without this
package taking a hard dependency on any of them:

```php
// app/Providers/EventServiceProvider.php (or AppServiceProvider::boot() on Laravel 11+)
use WatchDiff\Laravel\Events\WatcherChanged;

protected $listen = [
    WatcherChanged::class => [
        SendWatcherChangeNotification::class,
    ],
];
```

```php
use Illuminate\Support\Facades\Notification;
use WatchDiff\Laravel\Events\WatcherChanged;

final class SendWatcherChangeNotification
{
    public function handle(WatcherChanged $event): void
    {
        Notification::route('slack', config('services.slack.watchdiff_webhook'))
            ->notify(new WatcherChangedNotification($event->report));
    }
}
```

```php
use Illuminate\Support\Facades\Log;
use WatchDiff\Laravel\Events\WatcherChanged;

final class LogWatcherChange
{
    public function handle(WatcherChanged $event): void
    {
        Log::info('Watcher changed', ['url' => $event->report->url, 'changes' => \count($event->report->changes)]);
    }
}
```

For errors, there's no package-level event (config-file-driven watchers have no way to
express an `onError` closure from JSON) - but a programmatically-built `WatchConfig` can
still set `onError` directly and dispatch its own event from there:

```php
new WatchConfig(
    url: $url,
    onError: fn ($exception, $config) => event(new MyWatcherErrorEvent($exception, $config)),
);
```

## Database watchers

`WatchDiff\Core\Db\DbWatcher` is bound as a separate singleton, backed by its own storage
directory (`store.db_json_directory`) rather than the main `StoreInterface` binding:

```php
use WatchDiff\Core\Db\DbWatchConfig;
use WatchDiff\Core\Db\DbWatcher;

app(DbWatcher::class)->watch(new DbWatchConfig(
    connectionString: 'mysql://user:pass@localhost/shop',
    table: 'orders',
    primaryKey: ['id'],
));
```

The separate storage directory is deliberate, not a Laravel-specific quirk:
`watchdiff:clean` deletes any key in the main store that isn't a currently-configured
URL/file/cert watcher - if `DbWatcher` shared that storage, its own watchers would look
like orphans and get deleted.

`watchdiff:db` wraps this same service for a one-off Artisan check; the `watchdiff-core`
package also ships its own `vendor/bin/watchdiff db ...` binary that works identically
regardless of framework, if you'd rather not go through `artisan`.

## Scheduling

```php
// routes/console.php (Laravel 11+) or app/Console/Kernel.php::schedule() (Laravel 10)
use Illuminate\Support\Facades\Schedule;

Schedule::command('watchdiff:tick')->everyMinute();
```

Each watcher's own `interval` option (in `watchdiff.config.json`) decides how often it
actually runs - `watchdiff:tick` just checks which ones are due, so scheduling it every
minute is safe even with watchers that only need to run hourly or daily.

## Store drivers

- **`json`** (default): one `.json` file per watcher under `store.json_directory`, easy to
  inspect/diff for debugging, fine for a handful to a few dozen watchers.
- **`sqlite`**: a single `store.sqlite_path` file, better suited to many watchers or
  frequent ticks, needs `ext-pdo_sqlite`.

Both implement `WatchDiff\Core\Contract\StoreInterface` - to use your own backend (Redis,
an Eloquent-backed store, ...), implement that interface yourself and bind it in your own
app's service provider instead of using `store.driver`:

```php
$this->app->singleton(\WatchDiff\Core\Contract\StoreInterface::class, MyRedisStore::class);
```

(register this binding *after* `WatchDiffServiceProvider` runs, e.g. from your own
`AppServiceProvider::register()`, so it overrides the default.)

## What this package does and doesn't add

This package wraps every `watchdiff-core` CLI command as a `watchdiff:*` Artisan command,
**except `serve`**. `serve` binds its own raw TCP socket and runs its own accept loop (see
`watchdiff-core`'s README) - standing that up *inside* a Laravel app, which already has its
own HTTP server/routing, is architecturally the wrong shape, not just an unwrapped command.
If you want watcher status reachable over HTTP in a Laravel app, build a real route/
controller instead - see the [Controller example](#controller) above, which does the same
job (`WatchDiff::status()` as JSON) the idiomatic way. `watchdiff-core`'s own
`vendor/bin/watchdiff serve` binary is still there if you want the raw version regardless.

## Security

This package adds no HTTP-facing surface of its own (no routes, no controllers) - all of
`watchdiff-core`'s hardening (SSRF guards, response size caps, SQL-identifier validation,
credential-redacted storage keys, ...) applies unchanged. See `watchdiff-core`'s own README,
"Security considerations" section, for the full list. The one package-specific note: don't
point `store.json_directory`/`store.sqlite_path`/`store.db_json_directory` at a path your
web server serves directly (e.g. inside `public/`) - stored snapshots can contain the full
content of whatever you're watching.

## Commands

### `watchdiff:init`

```bash
php artisan watchdiff:init [--config=path/to/other.json]
```

Creates a starter config file at `config_path` (or `--config`) with one example watcher.
Refuses to overwrite an existing file.

### `watchdiff:tick`

```bash
php artisan watchdiff:tick [--config=path/to/other.json]
```

Checks every watcher from the config file whose `interval` has elapsed and prints a summary
line per changed watcher. This is the one to put on the scheduler.

### `watchdiff:check`

```bash
php artisan watchdiff:check https://example.com --target=.price --diff-mode=word
```

Runs a single one-off check against a URL, independent of any config file - useful for a
quick manual check, or for wiring a one-off check into your own application code path.

### `watchdiff:validate`

```bash
php artisan watchdiff:validate [--config=path/to/other.json]
```

Validates the config file: each watcher has a `url`/`localPath`/`certHost`, `url` is a
well-formed `http(s)://` URL, `interval` is a positive integer, `diffMode` is valid,
`jsonPath` starts with `$`, `schedule` is a parseable cron expression, and every `webhooks`
entry is a well-formed `http(s)://` URL. Every problem across every watcher is reported in
one pass, not just the first one found. Exits non-zero on any error - safe to run as a CI/
deploy-pipeline check before `watchdiff:tick` ever runs against a bad config.

### `watchdiff:list`

```bash
php artisan watchdiff:list [--config=path/to/other.json]
```

Prints every configured watcher with its paused state and check/change/error counters -
the config-file-driven equivalent of calling `WatchDiff::status()` yourself.

### `watchdiff:pause` / `watchdiff:resume`

```bash
php artisan watchdiff:pause <id|url|tag>
php artisan watchdiff:resume <id|url|tag>
```

Pauses/resumes a watcher (or every watcher sharing a `tags` entry) - `tick()` skips paused
watchers entirely. The pause flag is stored alongside the watcher's snapshots, so it
persists across deploys as long as the store itself does (don't point `store.json_directory`
at an ephemeral/per-request-wiped path).

### `watchdiff:clean`

```bash
php artisan watchdiff:clean [--config=path/to/other.json] [--yes]
```

Deletes stored data (snapshots, reports, state) for any watcher key that's no longer in the
config file - e.g. after removing a watcher from `watchdiff.config.json`. Lists what it's
about to delete and asks for confirmation unless `--yes` is passed; refuses to delete
anything in a non-interactive run (scheduler, CI) without `--yes`, rather than deleting
silently.

### `watchdiff:diff` / `watchdiff:history` / `watchdiff:snapshot` / `watchdiff:reports`

```bash
php artisan watchdiff:diff https://example.com          # last stored change
php artisan watchdiff:history https://example.com        # list of stored snapshots
php artisan watchdiff:snapshot https://example.com        # latest snapshot's raw content
php artisan watchdiff:reports https://example.com         # list of stored change reports
```

Read-only inspection of what's stored for a single URL, independent of the config file -
useful when debugging why a watcher did or didn't alert.

### `watchdiff:clear`

```bash
php artisan watchdiff:clear https://example.com
```

Deletes everything stored for a single URL (snapshots, reports, state) - the single-URL
counterpart to `watchdiff:clean`, which only removes watchers no longer in the config file.

### `watchdiff:export`

```bash
php artisan watchdiff:export https://example.com --format=csv --type=reports --output=reports.csv
```

Exports stored reports or snapshots as CSV or JSON, to stdout or to a file (`--output`).

### `watchdiff:compare`

```bash
php artisan watchdiff:compare https://example.com/v1 https://example.com/v2 --target=.price
```

Diffs two arbitrary URLs against each other directly, without storing anything - useful for
a one-off "are these two pages different" check.

### `watchdiff:db`

```bash
php artisan watchdiff:db "mysql://user:pass@localhost/shop" orders --primary-key=id
```

One-off check against a database table, through the same `DbWatcher` binding described in
[Database watchers](#database-watchers) above (so it uses `store.db_json_directory`, not
the main store).

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse src --level 5
```

Code style aligns consecutive `=` and `=>` signs within a block, done by hand rather than by
`php-cs-fixer fix` (see `watchdiff-core`'s README, "Development" section, for why) - CI only
runs PHPStan and PHPUnit, not a style-lint gate.

Tests use `orchestra/testbench` to boot a minimal Laravel application around the package.

## License

This project is licensed under the BSD 3-Clause License.

You are free to use, modify, and distribute this software in source and binary forms,
with or without modification, provided the copyright notice and disclaimer are
retained. No copyleft restrictions - compatible with MIT and proprietary projects.
