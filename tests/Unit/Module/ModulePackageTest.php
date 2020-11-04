<?php

declare(strict_types=1);

namespace Artemeon\Composer\Tests\Unit\Module;

use Artemeon\Composer\Module\ModulePackage;
use Artemeon\Composer\Tests\Unit\ComposerFileAssumptions;
use Composer\Package\Link;
use Composer\Package\RootPackageInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
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

    public function testMergesRequiresConfiguration(): void
    {
        $rootPackage = $this->createARootPackageWithExtendableRequiresConfiguration();
        $rootPackage->setRequires([
            'existing/dependency' => new Link($rootPackage->getName(), 'existing/dependency', new MatchAllConstraint())
        ]);

        $composerFile = self::assumeAValidComposerFileRequiring([
            'test/dependency' => '*',
        ]);

        $modulePackage = new ModulePackage($composerFile->getPath());
        $modulePackage->mergeRequires($rootPackage);

        $requires = $rootPackage->getRequires();
        self::assertArrayHasKey('existing/dependency', $requires);
        self::assertArrayHasKey('test/dependency', $requires);
    }

    public function testMergesDevRequiresConfiguration(): void
    {
        $rootPackage = $this->createARootPackageWithExtendableDevRequiresConfiguration();
        $rootPackage->setDevRequires([
            'existing/dev-dependency' => new Link($rootPackage->getName(), 'existing/dev-dependency', new MatchAllConstraint())
        ]);

        $composerFile = self::assumeAValidComposerFileDevRequiring([
            'test/dev-dependency' => '*',
        ]);

        $modulePackage = new ModulePackage($composerFile->getPath());
        $modulePackage->mergeRequires($rootPackage);

        $devRequires = $rootPackage->getDevRequires();
        self::assertArrayHasKey('existing/dev-dependency', $devRequires);
        self::assertArrayHasKey('test/dev-dependency', $devRequires);
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

    private function createARootPackageWithExtendableRequiresConfiguration(): RootPackageInterface
    {
        $rootPackage = $this->prophesize(RootPackageInterface::class);
        $rootPackage->getName()
            ->willReturn('test/package');
        $rootPackage->getRequires()
            ->will(function () use (&$requires): ?array {
                return $requires;
            });
        $rootPackage->setRequires(Argument::type('array'))
            ->will(function (array $arguments) use (&$requires): void {
                $requires = $arguments[0];
            });
        $rootPackage->getDevRequires()
            ->willReturn([]);
        $rootPackage->setDevRequires(Argument::type('array'))
            ->willReturn();

        return $rootPackage->reveal();
    }

    private function createARootPackageWithExtendableDevRequiresConfiguration(): RootPackageInterface
    {
        $rootPackage = $this->prophesize(RootPackageInterface::class);
        $rootPackage->getName()
            ->willReturn('test/package');
        $rootPackage->getDevRequires()
            ->will(function () use (&$devRequires): ?array {
                return $devRequires;
            });
        $rootPackage->setDevRequires(Argument::type('array'))
            ->will(function (array $arguments) use (&$devRequires): void {
                $devRequires = $arguments[0];
            });
        $rootPackage->getRequires()
            ->willReturn([]);
        $rootPackage->setRequires(Argument::type('array'))
            ->willReturn();

        return $rootPackage->reveal();
    }
}
