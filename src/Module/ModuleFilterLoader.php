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

    public function load(string $configurationFilePath, string $projectConfigurationFilePath): ModuleFilter
    {
        $project = 'default';
        if (is_file($projectConfigurationFilePath)) {
            $projectConfiguration = $this->readJsonFile($projectConfigurationFilePath);
            if ($this->validateProject($projectConfiguration)) {
                $project = $projectConfiguration->app;
            }
        }

        if (!is_dir('./apps/' . $project)) {
            $project = 'default';
        }

        $this->io->write('');
        $this->io->write(' Project: ' . $project);
        $this->io->write('');

        $this->io->debug(
            sprintf('loading module filter configuration at <comment>%s</comment>', './apps/' . $project . '/' . $configurationFilePath)
        );

        $configurationData = $this->readJsonFile('./apps/' . $project . '/' . $configurationFilePath);
        if (!isset($configurationData)) {
            return ModuleFilter::unrestricted($this->io);
        }

        if (!$this->validateConfiguration($configurationData)) {
            return ModuleFilter::unrestricted($this->io);
        }

        return ModuleFilter::restrictedTo($configurationData, $this->io);
    }

    /**
     * @return mixed|null
     */
    private function readJsonFile(string $filePath): mixed
    {
        $fileContents = @file_get_contents($filePath);
        if ($fileContents === false) {
            $this->io->warning('no module filter configuration given');
            $this->io->warning($filePath);

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

    private function validateConfiguration(mixed $configurationData): bool
    {
        $validator = new Validator();
        $validator->validate(
            $configurationData,
            (object)['$ref' => 'file://' . dirname(__DIR__, 2) . '/libs.schema.json']
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

    private function validateProject(mixed $configurationData): bool
    {
        $validator = new Validator();
        $validator->validate(
            $configurationData,
            (object)['$ref' => 'file://' . dirname(__DIR__, 2) . '/projectrc.schema.json']
        );

        if (!$validator->isValid()) {
            $this->io->warning('invalid project configuration');
            foreach ($validator->getErrors() as $error) {
                $this->io->warning(sprintf('</>	%s: %s', $error['property'], $error['message']));
            }

            return false;
        }

        return true;
    }
}
