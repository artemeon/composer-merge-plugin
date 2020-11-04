<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

final class ModulePackage
{
    private string $composerFile;

    public function __construct(string $composerFile)
    {
        $this->composerFile = $composerFile;
    }
}
