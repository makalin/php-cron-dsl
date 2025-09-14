<?php

declare(strict_types=1);

namespace CronDSL\Emitter;

use CronDSL\Unit\Unit;

/**
 * Emits systemd units to filesystem.
 */
class FilesystemEmitter implements EmitterInterface
{
    public function __construct(
        private readonly string $outputDirectory,
        private readonly string $prefix = '',
        private readonly bool $force = false
    ) {
        if (!is_dir($this->outputDirectory)) {
            if (!mkdir($this->outputDirectory, 0755, true)) {
                throw new \RuntimeException("Cannot create output directory: {$this->outputDirectory}");
            }
        }
    }

    public function write(array $units): void
    {
        foreach ($units as $unit) {
            $this->writeUnit($unit);
        }
    }

    private function writeUnit(Unit $unit): void
    {
        $name = $this->prefix . $unit->getName();

        // Write timer file
        $timerFile = $this->outputDirectory . '/' . $name . '.timer';
        $this->writeFile($timerFile, $unit->getTimer()->toIni());

        // Write service file
        $serviceFile = $this->outputDirectory . '/' . $name . '.service';
        $this->writeFile($serviceFile, $unit->getService()->toIni());
    }

    private function writeFile(string $filePath, string $content): void
    {
        if (file_exists($filePath) && !$this->force) {
            throw new \RuntimeException("File already exists: {$filePath}. Use --force to overwrite.");
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Cannot write file: {$filePath}");
        }
    }
}
