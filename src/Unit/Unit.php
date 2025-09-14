<?php

declare(strict_types=1);

namespace CronDSL\Unit;

/**
 * Represents a systemd unit pair (timer + service).
 */
class Unit
{
    public function __construct(
        public readonly string $name,
        public readonly Timer $timer,
        public readonly Service $service
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimer(): Timer
    {
        return $this->timer;
    }

    public function getService(): Service
    {
        return $this->service;
    }
}
