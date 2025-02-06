<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

final readonly class ModuleFilter implements ModuleFilterInterface
{
    private function __construct(
        private ?array $activeModules,
    ) {
    }

    public static function unrestricted(): self
    {
        return new self(null);
    }

    public static function restrictedTo(array $activeModules): self
    {
        return new self($activeModules);
    }

    public function shouldLoad(string $moduleName): bool
    {
        return !isset($this->activeModules) || in_array($moduleName, $this->activeModules, true);
    }
}
