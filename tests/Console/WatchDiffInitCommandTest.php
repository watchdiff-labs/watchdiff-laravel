<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Tests\Console;

use WatchDiff\Laravel\Tests\TestCase;

final class WatchDiffInitCommandTest extends TestCase
{
    public function testCreatesAStarterConfigFile(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);

        $this->artisan('watchdiff:init')->assertExitCode(0);

        self::assertFileExists($configPath);
        $data = json_decode((string) file_get_contents($configPath), true);
        self::assertArrayHasKey('watchers', $data);
    }

    public function testRefusesToOverwriteAnExistingFile(): void
    {
        $configPath = (string) config('watchdiff.config_path');
        @mkdir(\dirname($configPath), 0o775, true);
        file_put_contents($configPath, '{}');

        $this->artisan('watchdiff:init')->assertExitCode(1);
    }
}
