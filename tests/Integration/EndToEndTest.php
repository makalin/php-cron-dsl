<?php

declare(strict_types=1);

namespace CronDSL\Tests\Integration;

use CronDSL\Compiler\Compiler;
use CronDSL\Emitter\FilesystemEmitter;
use CronDSL\Emitter\StdoutEmitter;
use PHPUnit\Framework\TestCase;

class EndToEndTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cron-dsl-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testFullCompilationWorkflow(): void
    {
        $config = [
            [
                'name' => 'mailer',
                'schedule' => '*/5 * * * *',
                'command' => '/usr/bin/php /var/app/artisan queue:work --once',
                'user' => 'www-data',
                'working_dir' => '/var/app',
                'env' => ['APP_ENV' => 'prod'],
                'persistent' => true,
                'jitter' => '1min',
                'accuracy' => '30s',
                'timeout' => '10min',
                'nice' => 5,
                'cpu_quota' => '50%',
                'after' => ['network-online.target'],
                'description' => 'Queue worker',
            ],
            [
                'name' => 'backup',
                'calendar' => 'daily',
                'time' => '03:00',
                'command' => '/usr/local/bin/backup.sh',
                'persistent' => true,
                'user' => 'backup',
            ],
        ];

        $compiler = new Compiler('Europe/Istanbul', 'www-data');
        $units = $compiler->compile($config);

        $this->assertCount(2, $units);

        // Test stdout emitter
        $stdoutEmitter = new StdoutEmitter();
        ob_start();
        $stdoutEmitter->write($units);
        $output = ob_get_clean();

        $this->assertStringContainsString('mailer.timer', $output);
        $this->assertStringContainsString('mailer.service', $output);
        $this->assertStringContainsString('backup.timer', $output);
        $this->assertStringContainsString('backup.service', $output);

        // Test filesystem emitter
        $fsEmitter = new FilesystemEmitter($this->tempDir, 'app-', true);
        $fsEmitter->write($units);

        $this->assertFileExists($this->tempDir . '/app-mailer.timer');
        $this->assertFileExists($this->tempDir . '/app-mailer.service');
        $this->assertFileExists($this->tempDir . '/app-backup.timer');
        $this->assertFileExists($this->tempDir . '/app-backup.service');

        // Verify timer content
        $timerContent = file_get_contents($this->tempDir . '/app-mailer.timer');
        $this->assertStringContainsString('[Unit]', $timerContent);
        $this->assertStringContainsString('[Timer]', $timerContent);
        $this->assertStringContainsString('OnCalendar=*-*-* *:*/5:00', $timerContent);
        $this->assertStringContainsString('Persistent=yes', $timerContent);
        $this->assertStringContainsString('AccuracySec=30s', $timerContent);
        $this->assertStringContainsString('RandomizedDelaySec=1min', $timerContent);

        // Verify service content
        $serviceContent = file_get_contents($this->tempDir . '/app-mailer.service');
        $this->assertStringContainsString('[Unit]', $serviceContent);
        $this->assertStringContainsString('[Service]', $serviceContent);
        $this->assertStringContainsString('User=www-data', $serviceContent);
        $this->assertStringContainsString('WorkingDirectory=/var/app', $serviceContent);
        $this->assertStringContainsString('Environment=APP_ENV=prod TZ=Europe/Istanbul', $serviceContent);
        $this->assertStringContainsString('ExecStart=/usr/bin/php /var/app/artisan queue:work --once', $serviceContent);
        $this->assertStringContainsString('TimeoutStartSec=10min', $serviceContent);
        $this->assertStringContainsString('Nice=5', $serviceContent);
        $this->assertStringContainsString('CPUQuota=50%', $serviceContent);
    }

    public function testComplexCronExpressions(): void
    {
        $config = [
            [
                'name' => 'complex',
                'schedule' => '15,45 2-4 * * 1-5',
                'command' => '/usr/bin/php complex.php',
            ],
            [
                'name' => 'weekly',
                'schedule' => '@weekly',
                'command' => '/usr/bin/php weekly.php',
            ],
            [
                'name' => 'monthly',
                'schedule' => '0 0 1 * *',
                'command' => '/usr/bin/php monthly.php',
            ],
        ];

        $compiler = new Compiler();
        $units = $compiler->compile($config);

        $this->assertCount(3, $units);

        // Check that complex cron expressions are properly converted
        $complexTimer = $units[0]->getTimer();
        $this->assertStringContainsString('Mon..Fri', $complexTimer->getOnCalendar());
        $this->assertStringContainsString('02:15:00', $complexTimer->getOnCalendar());
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
