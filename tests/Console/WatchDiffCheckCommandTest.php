<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffCheckCommandTest extends TestCase
{
    public function testFirstCheckSavesASnapshotAndReportsNothingToCompare(): void
    {
        $httpClient = new Client();
        $psr17      = new Psr17Factory();
        $httpClient->addResponse($psr17->createResponse(200)->withBody($psr17->createStream('content')));

        $this->app->singleton(WatchDiff::class, fn ($app) => new WatchDiff(
            $app->make(StoreInterface::class),
            $httpClient,
            $psr17,
            $psr17,
        ));

        $this->artisan('watchdiff:check', ['url' => 'https://example.test/page'])
            ->expectsOutputToContain('First check')
            ->assertExitCode(0);
    }
}
