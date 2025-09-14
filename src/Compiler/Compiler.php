<?php

declare(strict_types=1);

namespace CronDSL\Compiler;

use CronDSL\Parser\CronParser;
use CronDSL\Unit\Service;
use CronDSL\Unit\Timer;
use CronDSL\Unit\Unit;

/**
 * Compiles PHP cron configuration arrays to systemd units.
 */
class Compiler
{
    private CronParser $parser;

    public function __construct(
        private readonly string $defaultTimezone = 'UTC',
        private readonly string $defaultUser = 'root'
    ) {
        $this->parser = new CronParser();
    }

    /**
     * Compile cron configuration to systemd units.
     *
     * @param array $config Array of cron job configurations
     * @return Unit[] Array of compiled units
     */
    public function compile(array $config): array
    {
        $units = [];

        foreach ($config as $job) {
            $this->validateJob($job);
            $units[] = $this->compileJob($job);
        }

        return $units;
    }

    private function validateJob(array $job): void
    {
        $required = ['name', 'command'];
        foreach ($required as $field) {
            if (!isset($job[$field])) {
                throw new \InvalidArgumentException("Missing required field '{$field}' in job configuration");
            }
        }

        // Either schedule or calendar must be present
        if (!isset($job['schedule']) && !isset($job['calendar'])) {
            throw new \InvalidArgumentException("Job must have either 'schedule' or 'calendar' field");
        }

        // Validate duration fields
        $durationFields = ['jitter', 'accuracy', 'timeout'];
        foreach ($durationFields as $field) {
            if (isset($job[$field]) && !$this->isValidDuration($job[$field])) {
                throw new \InvalidArgumentException("Invalid duration format for '{$field}': {$job[$field]}");
            }
        }

        // Validate nice value
        if (isset($job['nice']) && (!is_int($job['nice']) || $job['nice'] < -20 || $job['nice'] > 19)) {
            throw new \InvalidArgumentException('Nice value must be an integer between -20 and 19');
        }

        // Validate CPU quota
        if (isset($job['cpu_quota']) && !$this->isValidCpuQuota($job['cpu_quota'])) {
            throw new \InvalidArgumentException("Invalid CPU quota format: {$job['cpu_quota']}");
        }
    }

    private function compileJob(array $job): Unit
    {
        $name = $job['name'];
        $prefix = $job['prefix'] ?? '';
        $fullName = $prefix . $name;

        // Determine OnCalendar expression
        $onCalendar = $this->getOnCalendar($job);

        // Create timer
        $timer = new Timer(
            name: $fullName,
            description: $this->buildDescription($job),
            onCalendar: $onCalendar,
            persistent: $job['persistent'] ?? false,
            accuracy: $this->formatDuration($job['accuracy'] ?? '1min'),
            randomizedDelay: $this->formatDuration($job['jitter'] ?? null),
            after: $job['after'] ?? [],
            requires: $job['requires'] ?? []
        );

        // Create service
        $service = new Service(
            name: $fullName,
            description: $job['description'] ?? $name,
            command: $job['command'],
            user: $job['user'] ?? $this->defaultUser,
            workingDirectory: $job['working_dir'] ?? null,
            environment: $this->buildEnvironment($job),
            timeout: $this->formatDuration($job['timeout'] ?? '30min'),
            nice: $job['nice'] ?? null,
            cpuQuota: $job['cpu_quota'] ?? null,
            after: $job['after'] ?? [],
            requires: $job['requires'] ?? []
        );

        return new Unit($fullName, $timer, $service);
    }

    private function getOnCalendar(array $job): string
    {
        // Direct calendar specification
        if (isset($job['calendar'])) {
            $calendar = $job['calendar'];

            // Handle calendar aliases
            if (in_array($calendar, ['daily', 'weekly', 'monthly', 'yearly'])) {
                $calendar = $this->expandCalendarAlias($calendar);
            }

            // Add time if specified
            if (isset($job['time'])) {
                $calendar .= ' ' . $job['time'];
            }

            return $calendar;
        }

        // Parse cron expression
        $timezone = $job['env']['TZ'] ?? $this->defaultTimezone;

        return $this->parser->parseToOnCalendar($job['schedule'], $timezone);
    }

    private function expandCalendarAlias(string $alias): string
    {
        return match ($alias) {
            'daily' => 'daily',
            'weekly' => 'weekly',
            'monthly' => 'monthly',
            'yearly' => 'yearly',
            default => $alias,
        };
    }

    private function buildDescription(array $job): string
    {
        $description = $job['name'];

        if (isset($job['schedule'])) {
            $description .= ' (cron ' . $job['schedule'] . ')';
        } elseif (isset($job['calendar'])) {
            $description .= ' (calendar ' . $job['calendar'] . ')';
        }

        return $description;
    }

    private function buildEnvironment(array $job): array
    {
        $env = $job['env'] ?? [];

        // Add timezone if not already set
        if (!isset($env['TZ']) && $this->defaultTimezone !== 'UTC') {
            $env['TZ'] = $this->defaultTimezone;
        }

        return $env;
    }

    private function formatDuration(?string $duration): ?string
    {
        if ($duration === null) {
            return null;
        }

        // Convert duration to seconds
        $seconds = $this->parseDuration($duration);

        // Format as systemd duration
        if ($seconds >= 86400) { // 1 day or more
            $days = intval($seconds / 86400);
            $remaining = $seconds % 86400;
            if ($remaining === 0) {
                return $days . 'd';
            }

            return $days . 'd ' . $this->formatSeconds($remaining);
        }

        return $this->formatSeconds($seconds);
    }

    private function formatSeconds(int $seconds): string
    {
        if ($seconds >= 3600) { // 1 hour or more
            $hours = intval($seconds / 3600);
            $remaining = $seconds % 3600;
            if ($remaining === 0) {
                return $hours . 'h';
            }

            return $hours . 'h ' . $this->formatSeconds($remaining);
        }

        if ($seconds >= 60) { // 1 minute or more
            $minutes = intval($seconds / 60);
            $remaining = $seconds % 60;
            if ($remaining === 0) {
                return $minutes . 'min';
            }

            return $minutes . 'min ' . $remaining . 's';
        }

        return $seconds . 's';
    }

    private function parseDuration(string $duration): int
    {
        $duration = trim($duration);
        $total = 0;

        // Parse patterns like "1h 30min 45s"
        $patterns = [
            '/(\d+)d/' => 86400,
            '/(\d+)h/' => 3600,
            '/(\d+)min/' => 60,
            '/(\d+)s/' => 1,
        ];

        foreach ($patterns as $pattern => $multiplier) {
            if (preg_match_all($pattern, $duration, $matches)) {
                foreach ($matches[1] as $value) {
                    $total += (int) $value * $multiplier;
                }
            }
        }

        return $total;
    }

    private function isValidDuration(string $duration): bool
    {
        return $this->parseDuration($duration) > 0;
    }

    private function isValidCpuQuota(string $quota): bool
    {
        // Check for percentage format (e.g., "80%")
        if (preg_match('/^\d+%$/', $quota)) {
            $percent = (int) substr($quota, 0, -1);

            return $percent > 0 && $percent <= 100;
        }

        // Check for time format (e.g., "0.5s", "100ms")
        if (preg_match('/^\d+(\.\d+)?(s|ms|us)$/', $quota)) {
            return true;
        }

        return false;
    }
}
