<?php

declare(strict_types=1);

namespace Artemeon\Composer\Module;

use Composer\IO\IOInterface;
use JsonException;
use JsonSchema\Validator;

use function dirname;
use function sprintf;

use const JSON_OBJECT_AS_ARRAY;
use const JSON_THROW_ON_ERROR;

final class ModuleFilterLoader
{
    private IOInterface $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function load(string $configurationFilePath, string $baseConfigurationFilePath, string $localConfigurationFilePath): ModuleFilter
    {
        $this->io->debug(
            sprintf('loading module filter configuration at <comment>%s</comment>', $configurationFilePath)
        );

        $configurationData = $this->readJsonFile($configurationFilePath);
        if (!isset($configurationData)) {
            return ModuleFilter::unrestricted($this->io);
        }

        if (!$this->validateConfiguration($configurationData)) {
            return ModuleFilter::unrestricted($this->io);
        }

        $baseConfigurationData = $this->readJsonFile($baseConfigurationFilePath, false);
        $localConfigurationData = $this->readJsonFile($localConfigurationFilePath, false);

        $mergedConfiguration = array_values(array_unique([...$configurationData->core, ...($baseConfigurationData ?? []), ...($localConfigurationData ?? [])]));

        return ModuleFilter::restrictedTo($mergedConfiguration, $this->io);
    }

    /**
     * @return mixed|null
     */
    private function readJsonFile(string $filePath, bool $warning = true)
    {
        $fileContents = @file_get_contents($filePath);
        if ($fileContents === false) {
            if ($warning) {
                $this->io->warning('no module filter configuration given');
            }

            return null;
        }

        try {
            $jsonData = json_decode($fileContents, false, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->io->warning(sprintf('invalid module filter configuration: %s', $exception->getMessage()));

            return null;
        }

        return $jsonData;
    }

    /**
     * @param mixed $configurationData
     */
    private function validateConfiguration($configurationData): bool
    {
        $validator = new Validator();
        $validator->validate(
            $configurationData,
            (object)['$ref' => 'file://' . dirname(__DIR__, 2) . '/packageconfig.schema.json']
        );

        if (!$validator->isValid()) {
            $this->io->warning('invalid module filter configuration');
            foreach ($validator->getErrors() as $error) {
                $this->io->warning(sprintf('</>	%s: %s', $error['property'], $error['message']));
            }

            return false;
        }

        return true;
    }
}
