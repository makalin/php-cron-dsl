<?php

declare(strict_types=1);

namespace CronDSL\Tests\Unit;

use CronDSL\Unit\Service;
use CronDSL\Unit\Timer;
use CronDSL\Unit\Unit;
use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function testTimerToIni(): void
    {
        $timer = new Timer(
            name: 'test-timer',
            description: 'Test timer',
            onCalendar: '*-*-* *:*/5:00',
            persistent: true,
            accuracy: '1min',
            randomizedDelay: '30s'
        );

        $ini = $timer->toIni();

        $this->assertStringContainsString('[Unit]', $ini);
        $this->assertStringContainsString('Description=Test timer', $ini);
        $this->assertStringContainsString('[Timer]', $ini);
        $this->assertStringContainsString('OnCalendar=*-*-* *:*/5:00', $ini);
        $this->assertStringContainsString('AccuracySec=1min', $ini);
        $this->assertStringContainsString('RandomizedDelaySec=30s', $ini);
        $this->assertStringContainsString('Persistent=yes', $ini);
        $this->assertStringContainsString('Unit=test-timer.service', $ini);
    }

    public function testServiceToIni(): void
    {
        $service = new Service(
            name: 'test-service',
            description: 'Test service',
            command: '/usr/bin/php test.php',
            user: 'www-data',
            workingDirectory: '/var/app',
            environment: ['APP_ENV' => 'prod', 'TZ' => 'UTC'],
            timeout: '30min',
            nice: 5,
            cpuQuota: '80%',
            after: ['network-online.target'],
            requires: ['mysql.service']
        );

        $ini = $service->toIni();

        $this->assertStringContainsString('[Unit]', $ini);
        $this->assertStringContainsString('Description=Test service', $ini);
        $this->assertStringContainsString('After=network-online.target', $ini);
        $this->assertStringContainsString('Requires=mysql.service', $ini);
        $this->assertStringContainsString('[Service]', $ini);
        $this->assertStringContainsString('Type=oneshot', $ini);
        $this->assertStringContainsString('User=www-data', $ini);
        $this->assertStringContainsString('WorkingDirectory=/var/app', $ini);
        $this->assertStringContainsString('Environment=APP_ENV=prod TZ=UTC', $ini);
        $this->assertStringContainsString('ExecStart=/usr/bin/php test.php', $ini);
        $this->assertStringContainsString('TimeoutStartSec=30min', $ini);
        $this->assertStringContainsString('Nice=5', $ini);
        $this->assertStringContainsString('CPUQuota=80%', $ini);
    }

    public function testUnitCreation(): void
    {
        $timer = new Timer('test-timer', 'Test timer', '*-*-* *:*/5:00');
        $service = new Service('test-service', 'Test service', '/usr/bin/php test.php');
        $unit = new Unit('test', $timer, $service);

        $this->assertEquals('test', $unit->getName());
        $this->assertSame($timer, $unit->getTimer());
        $this->assertSame($service, $unit->getService());
    }
}
