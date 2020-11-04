<?php

declare(strict_types=1);

namespace Artemeon\Composer\Tests\Unit;

use Composer\Json\JsonFile;
use Composer\Package\Link;
use Symfony\Component\Filesystem\Filesystem;

use function array_merge;
use function sys_get_temp_dir;
use function uniqid;

trait ComposerFileAssumptions
{
    public static function assumeAValidComposerFileConfiguration(): array
    {
        return [
            'name' => 'test/package',
        ];
    }

    public static function assumeAValidComposerFileConfigurationAutoloading(array $autoloadMap): array
    {
        return array_merge(
            self::assumeAValidComposerFileConfiguration(),
            ['autoload' => $autoloadMap]
        );
    }

    public static function assumeAValidComposerFileConfigurationDevAutoloading(array $devAutoloadMap): array
    {
        return array_merge(
            self::assumeAValidComposerFileConfiguration(),
            ['autoload-dev' => $devAutoloadMap]
        );
    }

    public static function assumeAValidComposerFileConfigurationRequiring(array $requiresMap): array
    {
        return array_merge(
            self::assumeAValidComposerFileConfiguration(),
            ['require' => $requiresMap]
        );
    }

    public static function assumeAValidComposerFileConfigurationDevRequiring(array $devRequiresMap): array
    {
        return array_merge(
            self::assumeAValidComposerFileConfiguration(),
            ['require-dev' => $devRequiresMap]
        );
    }

    public static function assumeAValidComposerFile(): JsonFile
    {
        return self::createTemporaryJsonFile(
            self::assumeAValidComposerFileConfiguration()
        );
    }

    public static function assumeAValidComposerFileAutoloading(array $autoloadMap): JsonFile
    {
        return self::createTemporaryJsonFile(
            self::assumeAValidComposerFileConfigurationAutoloading($autoloadMap)
        );
    }

    public static function assumeAValidComposerFileDevAutoloading(array $devAutoloadMap): JsonFile
    {
        return self::createTemporaryJsonFile(
            self::assumeAValidComposerFileConfigurationDevAutoloading($devAutoloadMap)
        );
    }

    public static function assumeAValidComposerFileRequiring(array $requiresMap): JsonFile
    {
        return self::createTemporaryJsonFile(
            self::assumeAValidComposerFileConfigurationRequiring($requiresMap)
        );
    }

    public static function assumeAValidComposerFileDevRequiring(array $devRequiresMap): JsonFile
    {
        return self::createTemporaryJsonFile(
            self::assumeAValidComposerFileConfigurationDevRequiring($devRequiresMap)
        );
    }

    /**
     * @param mixed $content
     */
    private static function createTemporaryJsonFile($content): JsonFile
    {
        $filesystem = new Filesystem();
        $composerFile = $filesystem->tempnam(sys_get_temp_dir(), uniqid('', false));
        $filesystem->dumpFile($composerFile, JsonFile::encode($content));

        return new JsonFile($composerFile);
    }
}
