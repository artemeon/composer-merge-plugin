<?php

declare(strict_types=1);

namespace Artemeon\Composer\Tests\Unit\Module;

use Artemeon\Composer\Module\ModuleFilterLoader;
use Composer\IO\ConsoleIO;
use Composer\IO\NullIO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

final class ModuleFilterLoaderTest extends TestCase
{
    public function testLoadsUnrestrictedModuleFilterIfNoConfigurationIsPresent(): void
    {
        $moduleFilterLoader = new ModuleFilterLoader(new NullIO());
        $moduleFilter = $moduleFilterLoader->load($this->fixturePath('no_module_filter_configuration'));

        self::assertTrue($moduleFilter->shouldLoad('invalid_module_name'));
    }

    public function testLoadsUnrestrictedModuleFilterIfConfigurationIsInvalid(): void
    {
        $moduleFilterLoader = new ModuleFilterLoader(new NullIO());
        $moduleFilter = $moduleFilterLoader->load($this->fixturePath('invalid_module_filter_configuration'));

        self::assertTrue($moduleFilter->shouldLoad('invalid_module_name'));
    }

    public function testLoadsRestrictedModuleFilterIfConfigurationIsValid(): void
    {
        $moduleFilterLoader = new ModuleFilterLoader(new ConsoleIO(new ArrayInput([]), new ConsoleOutput(), new HelperSet()));
        $moduleFilter = $moduleFilterLoader->load($this->fixturePath('valid_module_filter_configuration'));

        self::assertTrue($moduleFilter->shouldLoad('module_test1'));
        self::assertTrue($moduleFilter->shouldLoad('module_test2'));
        self::assertFalse($moduleFilter->shouldLoad('invalid_module_name'));
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__) . '/fixtures/' . $name . '/packageconfig.json';
    }
}
