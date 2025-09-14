<?php

declare(strict_types=1);

namespace CronDSL\Emitter;

use CronDSL\Unit\Unit;

/**
 * Interface for emitting systemd units.
 */
interface EmitterInterface
{
    /**
     * Write units to the target destination.
     *
     * @param Unit[] $units Array of units to write
     */
    public function write(array $units): void;
}
