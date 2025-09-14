<?php

declare(strict_types=1);

namespace CronDSL\Unit;

/**
 * Represents a systemd timer unit.
 */
class Timer
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $onCalendar,
        public readonly bool $persistent = false,
        public readonly ?string $accuracy = null,
        public readonly ?string $randomizedDelay = null,
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

    public function getOnCalendar(): string
    {
        return $this->onCalendar;
    }

    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    public function getAccuracy(): ?string
    {
        return $this->accuracy;
    }

    public function getRandomizedDelay(): ?string
    {
        return $this->randomizedDelay;
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
            'Documentation=man:systemd.timer(5)',
            '',
            '[Timer]',
            'OnCalendar=' . $this->onCalendar,
        ];

        if ($this->accuracy !== null) {
            $lines[] = 'AccuracySec=' . $this->accuracy;
        }

        if ($this->randomizedDelay !== null) {
            $lines[] = 'RandomizedDelaySec=' . $this->randomizedDelay;
        } else {
            $lines[] = 'RandomizedDelaySec=0';
        }

        $lines[] = 'Persistent=' . ($this->persistent ? 'yes' : 'no');
        $lines[] = 'Unit=' . $this->name . '.service';

        if (!empty($this->after)) {
            $lines[] = '';
            $lines[] = '[Install]';
            $lines[] = 'WantedBy=timers.target';
        }

        return implode("\n", $lines);
    }
}
