<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

use Composer\IO\IOInterface;

use function glob;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const GLOB_NOESCAPE;
use const GLOB_NOSORT;

final class ModulePackageLoader
{
    private const MODULE_COMPOSER_FILE_PATTERN = 'module_*/composer.json';

    private IOInterface $io;
    private array $modulePackageCache = [];

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @return ModulePackage[]
     */
    public function load(string $basePath): iterable
    {
        foreach ($this->scanForComposerFiles($basePath) as $composerFile) {
            yield $this->loadModule($composerFile);
        }
    }

    private function scanForComposerFiles(string $basePath): iterable
    {
        $moduleComposerFilePattern = sprintf(
            '%s%s%s',
            rtrim($basePath, DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            self::MODULE_COMPOSER_FILE_PATTERN
        );
        $this->io->debug(sprintf('Loading modules using glob <comment>%s</comment>', $moduleComposerFilePattern));

        yield from glob($moduleComposerFilePattern, GLOB_NOSORT | GLOB_NOESCAPE);
    }

    private function loadModule(string $composerFile): ModulePackage
    {
        $this->io->info(sprintf('Loading module at <comment>%s</comment>', $composerFile));

        if (isset($this->modulePackageCache[$composerFile])) {
            return $this->modulePackageCache[$composerFile];
        }

        $modulePackage = new ModulePackage($composerFile);
        $this->modulePackageCache[$composerFile] = $modulePackage;

        return $modulePackage;
    }
}
