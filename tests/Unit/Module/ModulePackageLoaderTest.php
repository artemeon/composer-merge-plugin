<?php

declare(strict_types=1);

namespace Artemeon\Composer\Tests\Unit\Module;

use Artemeon\Composer\Module\ModulePackageLoader;
use Closure;
use Composer\IO\IOInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;

use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;

final class ModulePackageLoaderTest extends TestCase
{
    use ProphecyTrait;

    private Filesystem $filesystem;
    private IOInterface $nullIo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
        $this->nullIo = $this->prophesize(IOInterface::class)->reveal();
    }

    public function testLoadsModulesAtTheGivenPath(): void
    {
        $this->inWorkspaceContainingModulesNamed(['module_test1', 'module_test2'], function (string $basePath): void {
            $modulePackageLoader = new ModulePackageLoader($this->nullIo);
            self::assertCount(2, $modulePackageLoader->load($basePath));
        });
    }

    public function testOnlyLoadsModulesWhoseNameMatchesTheConvention(): void
    {
        $this->inWorkspaceContainingModulesNamed(['module_test1', 'test2'], function (string $basePath): void {
            $modulePackageLoader = new ModulePackageLoader($this->nullIo);
            self::assertCount(1, $modulePackageLoader->load($basePath));
        });
    }

    public function testOnlyLoadsModulesWhichContainAPackageFile(): void
    {
        $this->inWorkspaceContainingDirectoriesNamed(['module_test1'], function (string $basePath): void {
            $modulePackageLoader = new ModulePackageLoader($this->nullIo);
            self::assertCount(0, $modulePackageLoader->load($basePath));
        });
    }

    public function testLoadsNothingIfNoModulesExistAtTheGivenPath(): void
    {
        $this->inWorkspaceContainingDirectoriesNamed([], function (string $basePath): void {
            $modulePackageLoader = new ModulePackageLoader($this->nullIo);
            self::assertCount(0, $modulePackageLoader->load($basePath));
        });
    }

    private function createWorkspaceWithDirectoriesNamed(string ...$directoryNames): string
    {
        $basePath = $this->filesystem->tempnam(sys_get_temp_dir(), 'phpunit');
        $this->filesystem->remove($basePath);
        $this->filesystem->mkdir($basePath);

        foreach ($directoryNames as $directoryName) {
            $this->filesystem->mkdir($basePath . DIRECTORY_SEPARATOR . $directoryName);
        }

        return $basePath;
    }

    private function inWorkspaceContainingDirectoriesNamed(array $directoryNames, Closure $closure): void
    {
        $basePath = $this->createWorkspaceWithDirectoriesNamed(...$directoryNames);
        $closure($basePath);
        $this->filesystem->remove($basePath);
    }

    private function createWorkspaceWithModulesNamed(string ...$moduleNames): string
    {
        $basePath = $this->createWorkspaceWithDirectoriesNamed(...$moduleNames);

        foreach ($moduleNames as $moduleName) {
            $this->filesystem->touch(
                $basePath . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'composer.json'
            );
        }

        return $basePath;
    }

    private function inWorkspaceContainingModulesNamed(array $moduleNames, Closure $closure): void
    {
        $basePath = $this->createWorkspaceWithModulesNamed(...$moduleNames);
        $closure($basePath);
        $this->filesystem->remove($basePath);
    }
}
