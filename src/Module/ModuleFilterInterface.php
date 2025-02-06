<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

interface ModuleFilterInterface
{
    public function shouldLoad(string $moduleName): bool;
}
