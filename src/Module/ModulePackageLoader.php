<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

use Composer\IO\IOInterface;

use function basename;
use function dirname;
use function glob;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const GLOB_NOESCAPE;
use const GLOB_NOSORT;

final class ModulePackageLoader
{
    private const MODULE_COMPOSER_FILE_PATTERN = '*/composer.json';

    private ModuleFilterInterface $moduleFilter;
    private IOInterface $io;

    private array $modulePackageCache = [];

    public function __construct(ModuleFilterInterface $moduleFilter, IOInterface $io)
    {
        $this->moduleFilter = $moduleFilter;
        $this->io = $io;
    }

    /**
     * @return ModulePackage[]
     */
    public function load(string $basePath): iterable
    {
        $this->io->debug(
            sprintf(
                'loading modules at <comment>%s</comment> using <comment>%s</comment>',
                $basePath,
                self::MODULE_COMPOSER_FILE_PATTERN
            )
        );

        foreach ($this->scanForComposerFiles($basePath) as $composerFile) {
            $moduleName = basename(dirname($composerFile));

            if ($this->moduleFilter->shouldLoad($moduleName)) {
                yield $this->loadModule($composerFile);
            }
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
