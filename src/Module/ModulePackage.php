<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

use Artemeon\Composer\Exception\UnableToLoadModulePackageException;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\RootPackageInterface;

use function array_merge;
use function array_merge_recursive;
use function array_walk_recursive;
use function dirname;

final class ModulePackage
{
    private string $composerFile;
    private CompletePackage $package;

    public function __construct(string $composerFile)
    {
        $this->composerFile = $composerFile;
    }

    /**
     * @throws UnableToLoadModulePackageException
     */
    public function mergeRequires(RootPackageInterface $root): void
    {
        if (!isset($this->package)) {
            $this->package = $this->loadPackage();
        }

        $this->mergeRequire($root);
        $this->mergeRequireDev($root);
    }

    private function mergeRequire(RootPackageInterface $root): void
    {
        $root->setRequires(
            array_merge(
                $root->getRequires(),
                $this->package->getRequires()
            )
        );
    }

    private function mergeRequireDev(RootPackageInterface $root): void
    {
        $root->setDevRequires(
            array_merge(
                $root->getDevRequires(),
                $this->package->getDevRequires()
            )
        );
    }

    /**
     * @throws UnableToLoadModulePackageException
     */
    public function mergeAutoloads(RootPackageInterface $root): void
    {
        if (!isset($this->package)) {
            $this->package = $this->loadPackage();
        }

        $this->mergeAutoload($root);
        $this->mergeAutoloadDev($root);
    }

    private function mergeAutoload(RootPackageInterface $root): void
    {
        $root->setAutoload(
            array_merge_recursive(
                $root->getAutoload(),
                $this->fixRelativePaths($this->package->getAutoload())
            )
        );
    }

    private function mergeAutoloadDev(RootPackageInterface $root): void
    {
        $root->setDevAutoload(
            array_merge_recursive(
                $root->getDevAutoload(),
                $this->fixRelativePaths($this->package->getDevAutoload())
            )
        );
    }

    private function fixRelativePaths(array $paths): array
    {
        $basePath = dirname($this->composerFile);
        $basePath = ($basePath === '.') ? '' : "{$basePath}/";

        array_walk_recursive(
            $paths,
            static function (string &$path) use ($basePath): void {
                $path = "{$basePath}{$path}";
            }
        );

        return $paths;
    }

    private function loadPackage(): CompletePackage
    {
        $packageJson = (new JsonFile($this->composerFile))->read();
        if (!isset($packageJson['version'])) {
            $packageJson['version'] = '1.0.0';
        }

        $package = (new ArrayLoader())->load($packageJson);
        if (!($package instanceof CompletePackage)) {
            throw UnableToLoadModulePackageException::at($this->composerFile);
        }

        return $package;
    }
}
