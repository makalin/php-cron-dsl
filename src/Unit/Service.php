<?php

declare(strict_types=1);

namespace CronDSL\Unit;

/**
 * Represents a systemd service unit.
 */
class Service
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $command,
        public readonly ?string $user = null,
        public readonly ?string $workingDirectory = null,
        public readonly array $environment = [],
        public readonly ?string $timeout = null,
        public readonly ?int $nice = null,
        public readonly ?string $cpuQuota = null,
        public readonly array $after = [],
        public readonly array $requires = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getWorkingDirectory(): ?string
    {
        return $this->workingDirectory;
    }

    public function getEnvironment(): array
    {
        return $this->environment;
    }

    public function getTimeout(): ?string
    {
        return $this->timeout;
    }

    public function getNice(): ?int
    {
        return $this->nice;
    }

    public function getCpuQuota(): ?string
    {
        return $this->cpuQuota;
    }

    public function getAfter(): array
    {
        return $this->after;
    }

    public function getRequires(): array
    {
        return $this->requires;
    }

    public function toIni(): string
    {
        $lines = [
            '[Unit]',
            'Description=' . $this->description,
        ];

        if (!empty($this->after)) {
            $lines[] = 'After=' . implode(' ', $this->after);
        }

        if (!empty($this->requires)) {
            $lines[] = 'Requires=' . implode(' ', $this->requires);
        }

        $lines[] = '';
        $lines[] = '[Service]';
        $lines[] = 'Type=oneshot';

        if ($this->user !== null) {
            $lines[] = 'User=' . $this->user;
        }

        if ($this->workingDirectory !== null) {
            $lines[] = 'WorkingDirectory=' . $this->workingDirectory;
        }

        if (!empty($this->environment)) {
            $envVars = [];
            foreach ($this->environment as $key => $value) {
                $envVars[] = $key . '=' . $value;
            }
            $lines[] = 'Environment=' . implode(' ', $envVars);
        }

        $lines[] = 'ExecStart=' . $this->command;

        if ($this->timeout !== null) {
            $lines[] = 'TimeoutStartSec=' . $this->timeout;
        }

        if ($this->nice !== null) {
            $lines[] = 'Nice=' . $this->nice;
        }

        if ($this->cpuQuota !== null) {
            $lines[] = 'CPUQuota=' . $this->cpuQuota;
        }

        return implode("\n", $lines);
    }
}
