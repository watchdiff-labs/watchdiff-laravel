<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffValidateCommandTest extends TestCase
{
    public function testValidConfigReturnsSuccess(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode(['watchers' => [['url' => 'https://example.test']]]));

        $this->artisan('watchdiff:validate')
            ->expectsOutputToContain('valid')
            ->assertExitCode(0);
    }

    public function testInvalidUrlReturnsFailure(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, json_encode(['watchers' => [['url' => 'not-a-url']]]));

        $this->artisan('watchdiff:validate')
            ->expectsOutputToContain('http://')
            ->assertExitCode(1);
    }
}
