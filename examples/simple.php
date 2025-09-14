<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CronDSL\Compiler\Compiler;
use CronDSL\Emitter\FilesystemEmitter;

// Simple example configuration
$config = [
    [
        'name'     => 'hello-world',
        'schedule' => '*/10 * * * *',  // Every 10 minutes
        'command'  => 'echo "Hello from cron!"',
        'user'     => 'www-data',
    ],
    [
        'name'       => 'daily-backup',
        'calendar'   => 'daily',
        'time'       => '02:00',
        'command'    => '/usr/local/bin/backup.sh',
        'persistent' => true,
        'user'       => 'backup',
    ],
];

// Compile the configuration
$compiler = new Compiler('Europe/Istanbul', 'www-data');
$units = $compiler->compile($config);

echo "Compiled " . count($units) . " units:\n";
foreach ($units as $unit) {
    echo "- {$unit->getName()}\n";
}

// Write to filesystem
$outputDir = __DIR__ . '/../build/systemd';
$emitter = new FilesystemEmitter($outputDir, 'example-', true);
$emitter->write($units);

echo "\nUnits written to: {$outputDir}\n";
echo "Files created:\n";
foreach (glob($outputDir . '/*') as $file) {
    echo "- " . basename($file) . "\n";
}