<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

use Composer\IO\IOInterface;

use function in_array;

final class ModuleFilter
{
    /** @var string[]|null */
    private ?array $activeModules;
    private IOInterface $io;

    private function __construct(?array $activeModules, IOInterface $io)
    {
        $this->activeModules = $activeModules;
        $this->io = $io;
    }

    public static function unrestricted(IOInterface $io): self
    {
        return new self(null, $io);
    }

    public static function restrictedTo(array $activeModules, IOInterface $io): self
    {
        return new self($activeModules, $io);
    }

    public function shouldLoad(string $moduleName): bool
    {
        return !isset($this->activeModules) || in_array($moduleName, $this->activeModules, true);
    }
}
