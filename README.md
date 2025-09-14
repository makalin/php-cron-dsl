# php-cron-dsl

> **Cron-expression compiler → systemd timers**
> Generate native `.timer` / `.service` units from concise **PHP array configs**.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb3.svg)](#) [![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](#) [![Build](https://img.shields.io/badge/CI-passing-brightgreen.svg)](#)

---

## Why?

* Keep your schedules in **versioned PHP** (type-safe, testable).
* Emit **first-class systemd** units (no brittle crontab parsing).
* Ship per-job metadata: user, env, working dir, jitter, persistence, time zone.

---

## Install

```bash
composer require vendor/php-cron-dsl
# or: download a single-file PHAR from Releases → php-cron-dsl.phar
```

---

## Quick start (CLI)

```bash
# 1) Define jobs in config/cron.php
# 2) Compile to build/systemd
vendor/bin/cron-dsl compile config/cron.php --out build/systemd

# Optional:
#   --dry-run           # print generated units to STDOUT
#   --force             # overwrite existing files
#   --prefix app-       # prefix unit names (e.g., app-mailer.timer)
#   --timezone Europe/Istanbul
#   --user www-data     # default User= if not set per job
```

*Resulting tree:*

```
build/systemd/
  app-mailer.service
  app-mailer.timer
  app-reports.service
  app-reports.timer
```

---

## The DSL (PHP)

```php
<?php
// config/cron.php

return [
    // Minimal job
    [
        'name'    => 'mailer',
        'schedule'=> '*/5 * * * *',      // standard 5-field cron
        'command' => '/usr/bin/php /var/app/artisan queue:work --once',
    ],

    // Rich job
    [
        'name'      => 'reports',
        'schedule'  => '30 2 * * 1-5',   // 02:30 Mon–Fri
        'command'   => '/usr/bin/php /var/app/bin/make-reports.php',
        'user'      => 'reportbot',
        'working_dir' => '/var/app',
        'env'       => [ 'APP_ENV' => 'prod', 'TZ' => 'Europe/Istanbul' ],
        'persistent'=> true,             // systemd Persistent=
        'jitter'    => '3min',           // RandomizedDelaySec=
        'accuracy'  => '1min',           // AccuracySec=
        'timeout'   => '30min',          // TimeoutStartSec=
        'nice'      => 5,                // Nice=
        'cpu_quota' => '80%',            // CPUQuota=
        'after'     => ['network-online.target'],
        'requires'  => ['network-online.target'],
        'calendar'  => null,             // override OnCalendar directly (optional)
        'description'=> 'Daily BI report generation',
    ],

    // Calendar alias (no cron): every day at 03:00
    [
        'name'     => 'backup',
        'calendar' => 'daily',           // systemd known calendar spec
        'time'     => '03:00',           // appended to calendar (→ daily 03:00)
        'command'  => '/usr/local/bin/backup.sh',
        'persistent'=> true,
    ],
];
```

---

## Library usage (PHP)

```php
use CronDSL\Compiler;
use CronDSL\Emitter\FilesystemEmitter;

$config  = require __DIR__.'/config/cron.php';
$compiler= new Compiler(defaultTimezone: 'Europe/Istanbul', defaultUser: 'www-data');

$units = $compiler->compile($config);   // array of Unit objects (.timer + .service pairs)

$emitter = new FilesystemEmitter(__DIR__.'/build/systemd', prefix: 'app-');
$emitter->write($units);                // writes *.service and *.timer files
```

---

## Output examples

### `app-mailer.timer`

```ini
[Unit]
Description=mailer (cron */5 * * * *)
Documentation=man:systemd.timer(5)

[Timer]
OnCalendar=*-*-* *:*/5:00
AccuracySec=1min
RandomizedDelaySec=0
Persistent=no
Unit=app-mailer.service

[Install]
WantedBy=timers.target
```

### `app-mailer.service`

```ini
[Unit]
Description=mailer
After=network-online.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/app
Environment=APP_ENV=prod TZ=Europe/Istanbul
ExecStart=/usr/bin/php /var/app/artisan queue:work --once
TimeoutStartSec=30min
Nice=0
CPUQuota=80%
```

> Notes:
>
> * `OnCalendar` is derived from cron:
>   `*/5 * * * *` → `*-*-* *:*/5:00`
>   `30 2 * * 1-5` → `Mon..Fri *-*-* 02:30:00`
> * `Persistent=true` ensures missed runs trigger after downtime.
> * `jitter` → `RandomizedDelaySec`, `accuracy` → `AccuracySec`.

---

## Cron → OnCalendar mapping

The compiler converts 5-field cron to systemd `OnCalendar`:

| Cron field   | Example      | OnCalendar piece                         |
| ------------ | ------------ | ---------------------------------------- |
| Minute       | `*/5`, `30`  | `*:*:00` with `*/N` mapped to `*:*/N:00` |
| Hour         | `2`, `2-6`   | `HH:*:00` or ranges                      |
| Day of month | `*`, `1,15`  | `YYYY-MM-DD` part with lists/ranges      |
| Month        | `*`, `1-12`  | Month in date part                       |
| Day of week  | `1-5`, `Sun` | `Mon..Fri`, `Sun` segments               |

Specials:

* `@hourly`, `@daily`, `@weekly`, `@monthly`, `@yearly` recognized.
* `TZ` taken from job `env['TZ']` or compiler default.

---

## CLI reference

```text
cron-dsl compile <php-config>
  --out <dir>                 Output directory (required unless --dry-run)
  --prefix <str>              Prefix for unit names
  --timezone <Area/City>      Default time zone
  --user <name>               Default systemd User=
  --dry-run                   Print units to STDOUT
  --force                     Overwrite existing files
  --validate                  Validate only; no output
  --strict                    Fail on unknown keys or lossy cron
```

**Validate configs**

```bash
vendor/bin/cron-dsl compile config/cron.php --validate --strict
```

---

## Advanced keys

| Key           | Type         | Maps to                           |
| ------------- | ------------ | --------------------------------- |
| `persistent`  | bool         | `Persistent=` (timer)             |
| `jitter`      | duration     | `RandomizedDelaySec=`             |
| `accuracy`    | duration     | `AccuracySec=`                    |
| `timeout`     | duration     | `TimeoutStartSec=`                |
| `nice`        | int          | `Nice=`                           |
| `cpu_quota`   | `% / time`   | `CPUQuota=`                       |
| `after`       | string\[]    | `After=` (unit deps)              |
| `requires`    | string\[]    | `Requires=`                       |
| `env`         | k=>v         | `Environment=` lines              |
| `working_dir` | string       | `WorkingDirectory=`               |
| `calendar`    | string       | raw `OnCalendar=` (bypass cron)   |
| `time`        | HH\:MM(\:SS) | appended to `calendar` shorthands |

Durations accept `s|min|h|d|w` suffixes (e.g., `90s`, `3min`, `1h`).

---

## Deploy

```bash
# Copy generated units
sudo cp build/systemd/* /etc/systemd/system/

# Reload, enable, start timers
sudo systemctl daemon-reload
sudo systemctl enable app-mailer.timer app-reports.timer --now

# Inspect
systemctl list-timers | grep app-
journalctl -u app-reports.service -e
```

---

## Testing

```bash
composer test
# includes:
#  - Cron → OnCalendar property tests
#  - Round-trip fixtures (.php → .timer/.service)
#  - Static analysis (phpstan), CS (php-cs-fixer)
```

---

## FAQ

* **Why systemd instead of crontab?**
  Better observability (`journalctl`), dependencies, jitter, persistence, and per-job resource limits.

* **Non-Linux / launchd / Windows Task Scheduler?**
  Roadmap includes pluggable emitters; PRs welcome.

* **Exact time zone control?**
  Use `env['TZ']` per job or `--timezone` flag.

---

## Contributing

1. Fork & branch
2. `composer install`
3. Add tests for new cron features
4. Submit PR with before/after fixtures

---

## License

MIT © Mehmet T. AKALIN

---

## Credits

* systemd calendar syntax inspiration: `man systemd.time`
* Cron parsing rules based on standard 5-field cron semantics

---

## Tagline

**“Write schedules once, ship native timers.”**
