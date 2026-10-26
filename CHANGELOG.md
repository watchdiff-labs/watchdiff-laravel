# Changelog

## Unreleased

- Added `watchdiff:init`, `watchdiff:validate`, `watchdiff:list`, `watchdiff:pause`,
  `watchdiff:resume`, `watchdiff:clean`, `watchdiff:diff`, `watchdiff:history`,
  `watchdiff:snapshot`, `watchdiff:clear`, `watchdiff:reports`, `watchdiff:export`,
  `watchdiff:compare`, and `watchdiff:db` Artisan commands, bringing command coverage close
  to parity with `watchdiff-core`'s own CLI (`watchdiff:serve` is deliberately not wrapped -
  see the README's "What this package does and doesn't add").
- `WatchDiff\Core\Db\DbWatcher` is now bound as a singleton, backed by its own
  `store.db_json_directory` storage, separate from the main `StoreInterface` binding.
- `watchdiff:tick` now dispatches a `WatchDiff\Laravel\Events\WatcherChanged` event for
  every changed watcher, via Laravel's own event dispatcher - the integration point for
  notifications, queued jobs, and logging.
- `watchdiff:check` now distinguishes a first check (snapshot saved, nothing to compare
  yet) from a check that found no changes, matching `watchdiff-core`'s own CLI wording.

## 0.1.0

- Initial release: service container bindings for `WatchDiff`, JSON/SQLite store
  configuration, and the `watchdiff:tick` and `watchdiff:check` commands.
