<?php

declare(strict_types=1);

namespace Artemeon\Composer\Tests\Unit\Module;

use Artemeon\Composer\Module\ModulePackage;
use Artemeon\Composer\Tests\Unit\ComposerFileAssumptions;
use Composer\Package\RootPackageInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

final class ModulePackageTest extends TestCase
{
    use ProphecyTrait;
    use ComposerFileAssumptions;

    public function testMergesAutoloadConfiguration(): void
    {
        $composerFile = self::assumeAValidComposerFileAutoloading(
            ['psr-4' => ['Test\\Namespace\\' => 'src/']]
        );

        $rootPackage = $this->createARootPackageWithExtendableAutoloadConfiguration();
        $rootPackage->setAutoload(['psr-4' => ['Existing\\Namespace\\' => 'src/']]);

        $modulePackage = new ModulePackage($composerFile->getPath());
        $modulePackage->mergeAutoloads($rootPackage);

        $autoloadPsr4 = $rootPackage->getAutoload()['psr-4'];
        self::assertArrayHasKey('Test\\Namespace\\', $autoloadPsr4);
        self::assertArrayHasKey('Existing\\Namespace\\', $autoloadPsr4);
    }

    public function testMergesDevAutoloadConfiguration(): void
    {
        $composerFile = self::assumeAValidComposerFileDevAutoloading(
            ['psr-4' => ['Test\\Namespace\\Tests\\' => 'tests/']]
        );

        $rootPackage = $this->createARootPackageWithExtendableDevAutoloadConfiguration();
        $rootPackage->setDevAutoload(['psr-4' => ['Existing\\Namespace\\Tests\\' => 'tests/']]);

        $modulePackage = new ModulePackage($composerFile->getPath());
        $modulePackage->mergeAutoloads($rootPackage);

        $devAutoloadPsr4 = $rootPackage->getDevAutoload()['psr-4'];
        self::assertArrayHasKey('Test\\Namespace\\Tests\\', $devAutoloadPsr4);
        self::assertArrayHasKey('Existing\\Namespace\\Tests\\', $devAutoloadPsr4);
    }

    private function createARootPackageWithExtendableAutoloadConfiguration(): RootPackageInterface
    {
        $rootPackage = $this->prophesize(RootPackageInterface::class);
        $rootPackage->getAutoload()
            ->will(function () use (&$autoload): ?array {
                return $autoload;
            });
        $rootPackage->setAutoload(Argument::type('array'))
            ->will(function (array $arguments) use (&$autoload): void {
                $autoload = $arguments[0];
            });
        $rootPackage->getDevAutoload()
            ->willReturn([]);
        $rootPackage->setDevAutoload(Argument::type('array'))
            ->willReturn();

        return $rootPackage->reveal();
    }

    private function createARootPackageWithExtendableDevAutoloadConfiguration(): RootPackageInterface
    {
        $rootPackage = $this->prophesize(RootPackageInterface::class);
        $rootPackage->getDevAutoload()
            ->will(function () use (&$devAutoload): ?array {
                return $devAutoload;
            });
        $rootPackage->setDevAutoload(Argument::type('array'))
            ->will(function (array $arguments) use (&$devAutoload): void {
                $devAutoload = $arguments[0];
            });
        $rootPackage->getAutoload()
            ->willReturn([]);
        $rootPackage->setAutoload(Argument::type('array'))
            ->willReturn();

        return $rootPackage->reveal();
    }
}
