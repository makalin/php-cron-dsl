<?php

declare(strict_types=1);

namespace CronDSL\Tests\Unit;

use CronDSL\Compiler\Compiler;
use CronDSL\Unit\Unit;
use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase
{
    private Compiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler('UTC', 'www-data');
    }

    public function testBasicJobCompilation(): void
    {
        $config = [
            [
                'name' => 'test',
                'schedule' => '*/5 * * * *',
                'command' => '/usr/bin/php test.php',
            ],
        ];

        $units = $this->compiler->compile($config);

        $this->assertCount(1, $units);
        $this->assertInstanceOf(Unit::class, $units[0]);
        $this->assertEquals('test', $units[0]->getName());
    }

    public function testRichJobCompilation(): void
    {
        $config = [
            [
                'name' => 'reports',
                'schedule' => '30 2 * * 1-5',
                'command' => '/usr/bin/php reports.php',
                'user' => 'reportbot',
                'working_dir' => '/var/app',
                'env' => ['APP_ENV' => 'prod'],
                'persistent' => true,
                'jitter' => '3min',
                'accuracy' => '1min',
                'timeout' => '30min',
                'nice' => 5,
                'cpu_quota' => '80%',
                'after' => ['network-online.target'],
                'requires' => ['network-online.target'],
                'description' => 'Daily reports',
            ],
        ];

        $units = $this->compiler->compile($config);

        $this->assertCount(1, $units);
        $unit = $units[0];

        $timer = $unit->getTimer();
        $this->assertTrue($timer->isPersistent());
        $this->assertEquals('1min', $timer->getAccuracy());
        $this->assertEquals('3min', $timer->getRandomizedDelay());

        $service = $unit->getService();
        $this->assertEquals('reportbot', $service->getUser());
        $this->assertEquals('/var/app', $service->getWorkingDirectory());
        $this->assertEquals(['APP_ENV' => 'prod'], $service->getEnvironment());
        $this->assertEquals('30min', $service->getTimeout());
        $this->assertEquals(5, $service->getNice());
        $this->assertEquals('80%', $service->getCpuQuota());
    }

    public function testCalendarJobCompilation(): void
    {
        $config = [
            [
                'name' => 'backup',
                'calendar' => 'daily',
                'time' => '03:00',
                'command' => '/usr/local/bin/backup.sh',
                'persistent' => true,
            ],
        ];

        $units = $this->compiler->compile($config);

        $this->assertCount(1, $units);
        $timer = $units[0]->getTimer();
        $this->assertStringContainsString('daily 03:00', $timer->getOnCalendar());
    }

    public function testMissingRequiredFields(): void
    {
        $config = [
            [
                'schedule' => '*/5 * * * *',
                'command' => '/usr/bin/php test.php',
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required field 'name'");
        $this->compiler->compile($config);
    }

    public function testMissingScheduleOrCalendar(): void
    {
        $config = [
            [
                'name' => 'test',
                'command' => '/usr/bin/php test.php',
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Job must have either 'schedule' or 'calendar' field");
        $this->compiler->compile($config);
    }

    public function testInvalidDuration(): void
    {
        $config = [
            [
                'name' => 'test',
                'schedule' => '*/5 * * * *',
                'command' => '/usr/bin/php test.php',
                'jitter' => 'invalid',
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid duration format for 'jitter'");
        $this->compiler->compile($config);
    }

    public function testInvalidNiceValue(): void
    {
        $config = [
            [
                'name' => 'test',
                'schedule' => '*/5 * * * *',
                'command' => '/usr/bin/php test.php',
                'nice' => 25, // Invalid nice value
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nice value must be an integer between -20 and 19');
        $this->compiler->compile($config);
    }

    public function testInvalidCpuQuota(): void
    {
        $config = [
            [
                'name' => 'test',
                'schedule' => '*/5 * * * *',
                'command' => '/usr/bin/php test.php',
                'cpu_quota' => 'invalid',
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CPU quota format');
        $this->compiler->compile($config);
    }

    public function testDefaultValues(): void
    {
        $config = [
            [
                'name' => 'test',
                'schedule' => '*/5 * * * *',
                'command' => '/usr/bin/php test.php',
            ],
        ];

        $units = $this->compiler->compile($config);
        $service = $units[0]->getService();

        $this->assertEquals('www-data', $service->getUser());
        $this->assertEquals('30min', $service->getTimeout());
        $this->assertEquals([], $service->getEnvironment());
    }
}
