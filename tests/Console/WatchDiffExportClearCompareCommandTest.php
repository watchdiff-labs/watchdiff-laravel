<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use WatchDiff\Core\Config\WatchConfig;
use WatchDiff\Core\Contract\StoreInterface;
use WatchDiff\Core\WatchDiff;
use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffExportClearCompareCommandTest extends TestCase
{
    private Client $httpClient;
    private Psr17Factory $psr17;
    private WatchDiff $watchDiff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = new Client();
        $this->psr17      = new Psr17Factory();
        $this->watchDiff  = new WatchDiff($this->app->make(StoreInterface::class), $this->httpClient, $this->psr17, $this->psr17);
        $this->app->singleton(WatchDiff::class, fn () => $this->watchDiff);
    }

    public function testExportWritesReportsToAFileWhenOutputIsGiven(): void
    {
        $config = new WatchConfig(url: 'https://example.test/page');
        $this->watchDiff->watch($config);
        $this->httpClient->addResponse($this->psr17->createResponse(200)->withBody($this->psr17->createStream('a')));
        $this->watchDiff->checkOnce($config->key());
        $this->httpClient->addResponse($this->psr17->createResponse(200)->withBody($this->psr17->createStream('b')));
        $this->watchDiff->checkOnce($config->key());

        $outputPath = sys_get_temp_dir().'/watchdiff-laravel-export-'.bin2hex(random_bytes(8)).'.json';

        try {
            $this->artisan('watchdiff:export', ['url' => 'https://example.test/page', '--output' => $outputPath])
                ->assertExitCode(0);

            self::assertFileExists($outputPath);
            self::assertStringContainsString('"a"', (string) file_get_contents($outputPath));
        } finally {
            @unlink($outputPath);
        }
    }

    public function testClearRemovesAllStoredDataForTheUrl(): void
    {
        $config = new WatchConfig(url: 'https://example.test/page');
        $this->watchDiff->watch($config);
        $this->httpClient->addResponse($this->psr17->createResponse(200)->withBody($this->psr17->createStream('a')));
        $this->watchDiff->checkOnce($config->key());

        $this->artisan('watchdiff:clear', ['url' => 'https://example.test/page'])->assertExitCode(0);

        self::assertSame([], $this->watchDiff->storedKeys());
    }

    public function testCompareDiffsTwoArbitraryUrls(): void
    {
        $this->httpClient->addResponse($this->psr17->createResponse(200)->withBody($this->psr17->createStream('a')));
        $this->httpClient->addResponse($this->psr17->createResponse(200)->withBody($this->psr17->createStream('b')));

        $this->artisan('watchdiff:compare', ['urlA' => 'https://example.test/a', 'urlB' => 'https://example.test/b'])
            ->expectsOutputToContain('a -> b')
            ->assertExitCode(0);
    }
}
