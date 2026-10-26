<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffDiffHistorySnapshotReportsCommandTest extends TestCase
{
    private WatchDiff $watchDiff;

    protected function setUp(): void
    {
        parent::setUp();

        $httpClient = new Client();
        $psr17      = new Psr17Factory();
        $this->watchDiff = new WatchDiff($this->app->make(StoreInterface::class), $httpClient, $psr17, $psr17);
        $this->app->singleton(WatchDiff::class, fn () => $this->watchDiff);

        $config = new WatchConfig(url: 'https://example.test/page');
        $this->watchDiff->watch($config);

        $httpClient->addResponse($psr17->createResponse(200)->withBody($psr17->createStream('first')));
        $this->watchDiff->checkOnce($config->key());
        $httpClient->addResponse($psr17->createResponse(200)->withBody($psr17->createStream('second')));
        $this->watchDiff->checkOnce($config->key());
    }

    public function testDiffPrintsTheLastStoredChange(): void
    {
        $this->artisan('watchdiff:diff', ['url' => 'https://example.test/page'])
            ->expectsOutputToContain('first -> second')
            ->assertExitCode(0);
    }

    public function testHistoryListsBothSnapshots(): void
    {
        $this->artisan('watchdiff:history', ['url' => 'https://example.test/page'])->assertExitCode(0);
    }

    public function testSnapshotPrintsTheLatestContent(): void
    {
        $this->artisan('watchdiff:snapshot', ['url' => 'https://example.test/page'])
            ->expectsOutputToContain('second')
            ->assertExitCode(0);
    }

    public function testReportsListsTheStoredReport(): void
    {
        $this->artisan('watchdiff:reports', ['url' => 'https://example.test/page'])
            ->expectsOutputToContain('1 change(s)')
            ->assertExitCode(0);
    }
}
