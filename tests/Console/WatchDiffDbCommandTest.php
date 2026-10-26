<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffDbCommandTest extends TestCase
{
    private string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir().'/watchdiff-laravel-db-'.bin2hex(random_bytes(8)).'.sqlite';

        if (!\in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is not installed in this environment.');
        }

        $pdo = new \PDO('sqlite:'.$this->dbPath);
        $pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO products (id, name) VALUES (1, 'Widget')");
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
        parent::tearDown();
    }

    public function testFirstCheckSavesASnapshot(): void
    {
        $this->artisan('watchdiff:db', [
            'connectionString' => 'sqlite://'.$this->dbPath,
            'table'            => 'products',
            '--primary-key'    => ['id'],
        ])
            ->expectsOutputToContain('First check')
            ->assertExitCode(0);
    }
}
