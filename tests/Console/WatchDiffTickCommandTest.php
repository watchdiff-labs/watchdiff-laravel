<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use Http\Mock\Client;
use Illuminate\Support\Facades\Event;
use Nyholm\Psr7\Factory\Psr17Factory;
use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Events\WatcherChanged;
use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffTickCommandTest extends TestCase
{
    public function testTicksEveryWatcherFromTheConfigFile(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode([
            'watchers' => [['url' => 'https://example.test/page']],
        ]));

        $httpClient = new Client();
        $psr17      = new Psr17Factory();
        $httpClient->addResponse($psr17->createResponse(200)->withBody($psr17->createStream('content')));

        $this->app->singleton(WatchDiff::class, fn ($app) => new WatchDiff(
            $app->make(StoreInterface::class),
            $httpClient,
            $psr17,
            $psr17,
        ));

        $this->artisan('watchdiff:tick')
            ->expectsOutputToContain('No changes detected')
            ->assertExitCode(0);
    }

    public function testDispatchesWatcherChangedEventForEveryChangedWatcher(): void
    {
        Event::fake([WatcherChanged::class]);

        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode([
            'watchers' => [['url' => 'https://example.test/page', 'interval' => 0]],
        ]));

        $httpClient = new Client();
        $psr17      = new Psr17Factory();
        $watchDiff  = new WatchDiff($this->app->make(StoreInterface::class), $httpClient, $psr17, $psr17);
        $this->app->singleton(WatchDiff::class, fn () => $watchDiff);

        $config = new \WatchDiff\Core\Config\WatchConfig(url: 'https://example.test/page', interval: 0);
        $watchDiff->watch($config);
        $httpClient->addResponse($psr17->createResponse(200)->withBody($psr17->createStream('before')));
        $watchDiff->checkOnce($config->key());

        $httpClient->addResponse($psr17->createResponse(200)->withBody($psr17->createStream('after')));
        $this->artisan('watchdiff:tick')->assertExitCode(0);

        Event::assertDispatched(WatcherChanged::class, fn (WatcherChanged $event) => $event->report->hasChanges());
    }
}
