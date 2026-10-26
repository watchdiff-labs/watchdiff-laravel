<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffPauseResumeCommandTest extends TestCase
{
    public function testPauseThenResumeRoundTrips(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode([
            'watchers' => [['url' => 'https://example.test/page', 'id' => 'w1']],
        ]));

        $httpClient = new Client();
        $psr17      = new Psr17Factory();
        $watchDiff  = new WatchDiff($this->app->make(StoreInterface::class), $httpClient, $psr17, $psr17);
        $this->app->singleton(WatchDiff::class, fn () => $watchDiff);

        $this->artisan('watchdiff:pause', ['tagOrId' => 'w1'])
            ->expectsOutputToContain('Paused')
            ->assertExitCode(0);

        $httpClient->addResponse($psr17->createResponse(200)->withBody($psr17->createStream('content')));
        self::assertSame([], $watchDiff->tick(), 'a paused watcher must be skipped by tick()');

        $this->artisan('watchdiff:resume', ['tagOrId' => 'w1'])
            ->expectsOutputToContain('Resumed')
            ->assertExitCode(0);
    }
}
