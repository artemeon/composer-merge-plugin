<?php

declare(strict_types=1);

namespace Artemeon\Composer\Tests\Unit\Module;

use Artemeon\Composer\Module\ModuleFilter;
use Composer\IO\NullIO;
use PHPUnit\Framework\TestCase;

use function array_slice;
use function ceil;
use function count;
use function in_array;
use function random_bytes;
use function shuffle;

final class ModuleFilterTest extends TestCase
{
    public function testApprovesLoadingOfEveryModuleIfNotRestricted(): void
    {
        $moduleFilter = ModuleFilter::unrestricted(new NullIO());

        foreach ($this->aFewModuleNames() as $moduleName) {
            self::assertTrue($moduleFilter->shouldLoad($moduleName));
        }
    }

    public function testOnlyApprovesLoadingModulesItWasRestrictedTo(): void
    {
        $allModuleNames = $this->aFewModuleNames();
        $approvedModuleNames = array_slice($allModuleNames, 0, ceil(count($allModuleNames) / 2));
        shuffle($allModuleNames);

        $moduleFilter = ModuleFilter::restrictedTo($approvedModuleNames, new NullIO());

        foreach ($allModuleNames as $moduleName) {
            if (in_array($moduleName, $approvedModuleNames, true)) {
                self::assertTrue($moduleFilter->shouldLoad($moduleName));
            } else {
                self::assertFalse($moduleFilter->shouldLoad($moduleName));
            }
        }
    }

    private function aFewModuleNames(): array
    {
        $moduleNames = [];
        for ($iteration = 0; $iteration < 10; ++$iteration) {
            /** @noinspection PhpUnhandledExceptionInspection because entropy gathering issues are out of scope here */
            $moduleNames[] = 'module_' . random_bytes(8);
        }

        return $moduleNames;
    }
}
