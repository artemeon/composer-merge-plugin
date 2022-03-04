<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

final class ModuleIncludeAllFilter implements ModuleFilterInterface
{
    public function shouldLoad(string $moduleName): bool
    {
        return true;
    }
}
