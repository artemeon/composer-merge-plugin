<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

use Composer\IO\IOInterface;
use JsonException;

final readonly class ModuleFilterLoader
{
    public function __construct(private IOInterface $io)
    {
    }

    public function load(string $configurationFilePath, string $localConfigurationFilePath): ModuleFilterInterface
    {
        $this->io->debug(
            sprintf('Loading module filter configuration at <comment>%s</comment>', $configurationFilePath),
        );

        $configurationData = $this->readJsonFile($configurationFilePath);
        if (!isset($configurationData)) {
            return ModuleFilter::unrestricted();
        }

        $localConfigurationData = $this->readJsonFile($localConfigurationFilePath);

        $mergedConfiguration = array_values(array_unique([...$configurationData->core, ...($localConfigurationData ?? [])]));

        return ModuleFilter::restrictedTo($mergedConfiguration);
    }

    private function readJsonFile(string $filePath): ?object
    {
        $fileContents = @file_get_contents($filePath);
        if ($fileContents === false) {
            $this->io->warning('No module filter configuration found');

            return null;
        }

        try {
            $jsonData = json_decode($fileContents, false, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->io->warning(sprintf('Invalid module filter configuration: %s', $exception->getMessage()));

            return null;
        }

        return $jsonData;
    }
}
