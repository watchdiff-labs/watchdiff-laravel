<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Console;

use Illuminate\Console\Command;
use WatchDiff\Core\Db\DbDiffMode;
use WatchDiff\Core\Db\DbWatchConfig;
use WatchDiff\Core\Db\DbWatcher;

final class WatchDiffDbCommand extends Command
{
    protected $signature = 'watchdiff:db {connectionString : e.g. sqlite://./app.db} {table}
        {--primary-key=* : Primary key column(s)}
        {--diff-mode=row : row|schema|aggregate|value}
        {--query=}
        {--threshold=0 : Percent change required to alert (aggregate mode)}';

    protected $description = 'Run a single one-off check against a database table.';

    public function handle(DbWatcher $dbWatcher): int
    {
        $config = new DbWatchConfig(
            connectionString: (string) $this->argument('connectionString'),
            table: (string) $this->argument('table'),
            diffMode: DbDiffMode::from((string) $this->option('diff-mode')),
            primaryKey: $this->option('primary-key'),
            query: $this->option('query'),
            threshold: (float) $this->option('threshold'),
        );

        $dbWatcher->watch($config);
        $report = $dbWatcher->checkOnce($config->key());

        if (null === $report) {
            $this->info('First check - snapshot saved, nothing to compare yet.');

            return self::SUCCESS;
        }

        if (!$report->hasChanges()) {
            $this->info('No changes detected.');

            return self::SUCCESS;
        }

        foreach ($report->changes as $change) {
            $this->line(sprintf(
                '[%s]%s%s',
                $change->kind->value,
                null !== $change->rowKey ? ' key='.$change->rowKey : '',
                null !== $change->column ? ' column='.$change->column.' ('.$change->context.')' : '',
            ));
        }

        return self::SUCCESS;
    }
}
