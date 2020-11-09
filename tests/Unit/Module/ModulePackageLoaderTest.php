<?php

declare(strict_types=1);

namespace Artemeon\Composer\Tests\Unit\Module;

use Artemeon\Composer\Module\ModuleFilter;
use Artemeon\Composer\Module\ModulePackageLoader;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

use function dirname;

final class ModulePackageLoaderTest extends TestCase
{
    use ProphecyTrait;

    private IOInterface $nullIo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nullIo = new NullIO();
    }

    public function testLoadsModulesAtTheGivenPath(): void
    {
        $modulePackageLoader = new ModulePackageLoader($this->nullIo);
        self::assertCount(2, $modulePackageLoader->load($this->fixturePath('two_modules')));
    }

    public function testOnlyLoadsModulesWhoseNameMatchesTheConvention(): void
    {
        $modulePackageLoader = new ModulePackageLoader($this->nullIo);
        self::assertCount(1, $modulePackageLoader->load($this->fixturePath('two_modules_one_invalid_name')));
    }

    public function testOnlyLoadsModulesWhichContainAPackageFile(): void
    {
        $modulePackageLoader = new ModulePackageLoader($this->nullIo);
        self::assertCount(1, $modulePackageLoader->load($this->fixturePath('one_module_and_one_directory')));
        $modulePackageLoader = new ModulePackageLoader($this->nullIo);
        self::assertCount(0, $modulePackageLoader->load($this->fixturePath('two_directories')));
    }

    public function testLoadsNothingIfNoModulesExistAtTheGivenPath(): void
    {
        $modulePackageLoader = new ModulePackageLoader($this->nullIo);
        self::assertCount(0, $modulePackageLoader->load($this->fixturePath('no_modules')));
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__) . '/fixtures/' . $name;
    }
}
