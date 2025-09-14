<?php

declare(strict_types=1);

return [
    // Minimal job
    [
        'name'     => 'mailer',
        'schedule' => '*/5 * * * *',      // standard 5-field cron
        'command'  => '/usr/bin/php /var/app/artisan queue:work --once',
    ],

    // Rich job
    [
        'name'         => 'reports',
        'schedule'     => '30 2 * * 1-5',   // 02:30 Mon–Fri
        'command'      => '/usr/bin/php /var/app/bin/make-reports.php',
        'user'         => 'reportbot',
        'working_dir' => '/var/app',
        'env'          => ['APP_ENV' => 'prod', 'TZ' => 'Europe/Istanbul'],
        'persistent'   => true,             // systemd Persistent=
        'jitter'       => '3min',           // RandomizedDelaySec=
        'accuracy'     => '1min',           // AccuracySec=
        'timeout'      => '30min',          // TimeoutStartSec=
        'nice'         => 5,                // Nice=
        'cpu_quota'    => '80%',            // CPUQuota=
        'after'        => ['network-online.target'],
        'requires'     => ['network-online.target'],
        'description'  => 'Daily BI report generation',
    ],

    // Calendar alias (no cron): every day at 03:00
    [
        'name'       => 'backup',
        'calendar'   => 'daily',           // systemd known calendar spec
        'time'       => '03:00',           // appended to calendar (→ daily 03:00)
        'command'    => '/usr/local/bin/backup.sh',
        'persistent' => true,
    ],

    // Hourly cleanup job
    [
        'name'        => 'cleanup',
        'schedule'    => '@hourly',
        'command'     => '/usr/bin/find /tmp -type f -mtime +7 -delete',
        'user'        => 'www-data',
        'working_dir' => '/tmp',
        'timeout'     => '5min',
        'nice'        => 10,
    ],

    // Weekly maintenance
    [
        'name'        => 'maintenance',
        'schedule'    => '0 3 * * 0',     // Sunday at 3 AM
        'command'     => '/usr/bin/php /var/app/artisan maintenance:run',
        'user'        => 'www-data',
        'working_dir' => '/var/app',
        'env'         => ['APP_ENV' => 'production'],
        'persistent'  => true,
        'jitter'      => '15min',
        'timeout'     => '2h',
        'after'       => ['network-online.target', 'mysql.service'],
        'description' => 'Weekly system maintenance',
    ],
];