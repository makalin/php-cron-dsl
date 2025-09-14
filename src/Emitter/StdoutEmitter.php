<?php

declare(strict_types=1);

namespace CronDSL\Emitter;

use CronDSL\Unit\Unit;

/**
 * Emits systemd units to stdout.
 */
class StdoutEmitter implements EmitterInterface
{
    public function write(array $units): void
    {
        foreach ($units as $unit) {
            $this->writeUnit($unit);
        }
    }

    private function writeUnit(Unit $unit): void
    {
        echo "=== {$unit->getName()}.timer ===\n";
        echo $unit->getTimer()->toIni() . "\n\n";

        echo "=== {$unit->getName()}.service ===\n";
        echo $unit->getService()->toIni() . "\n\n";
    }
}
