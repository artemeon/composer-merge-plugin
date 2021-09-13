<?php

declare(strict_types=1);

namespace Artemeon\Composer\Plugin;

use Artemeon\Composer\Module\ModuleFilterLoader;
use Artemeon\Composer\Module\ModulePackageLoader;
use Artemeon\Composer\Module\ModuleIncludeAllFilter;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\Installer;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;

use function glob;

final class MergePlugin implements PluginInterface, EventSubscriberInterface
{
    private const CALLBACK_PRIORITY = 50000;
    private const MODULES_BASE_PATH = '../core';
    private const OVERRIDDEN_MODULES = './module_*';
    private const FILTER_CONFIGURATION_PATH = './packageconfig.json';

    private Composer $composer;
    private IOInterface $io;
    private ModulePackageLoader $modulePackageLoader;

    protected bool $isFirstInstall = false;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $packageConfig = $composer->getPackage()->getExtra()['packageconfig'] ?? true;
        if ($packageConfig) {
            $moduleFilter = (new ModuleFilterLoader($io))->load(self::FILTER_CONFIGURATION_PATH);
        } else {
            $moduleFilter = new ModuleIncludeAllFilter();
        }

        $this->modulePackageLoader = new ModulePackageLoader($moduleFilter, $io);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => ['preInstallOrUpdate', static::CALLBACK_PRIORITY],
            ScriptEvents::PRE_UPDATE_CMD => ['preInstallOrUpdate', static::CALLBACK_PRIORITY],
            ScriptEvents::POST_INSTALL_CMD => ['postInstallOrUpdate', static::CALLBACK_PRIORITY],
            ScriptEvents::POST_UPDATE_CMD => ['postInstallOrUpdate', static::CALLBACK_PRIORITY],
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['preAutoloadDump', static::CALLBACK_PRIORITY],
            PackageEvents::POST_PACKAGE_INSTALL => ['postPackageInstall', static::CALLBACK_PRIORITY],
        ];
    }

    public function preInstallOrUpdate(ScriptEvent $event): void
    {
        $this->mergeRequires($this->composer->getPackage());
    }

    public function postInstallOrUpdate(ScriptEvent $event): void
    {
        if (!$this->isFirstInstall) {
            return;
        }

        $this->isFirstInstall = false;
        $this->runAdditionalUpdateToApplyMergedConfiguration($event);
    }

    public function preAutoloadDump(ScriptEvent $event): void
    {
        $rootPackage = $this->composer->getPackage();
        $this->mergeAutoloads($rootPackage);

        $overrides = $rootPackage->getExtra()['overrides'] ?? true;
        if ($overrides) {
            $this->mergeAutoloadOverrides($rootPackage);
        }
    }

    public function postPackageInstall(PackageEvent $event): void
    {
        $this->recognizePluginInstallation($event);
    }

    private function mergeAutoloads(RootPackageInterface $rootPackage): void
    {
        foreach ($this->modulePackageLoader->load($this->getBasePath($rootPackage)) as $modulePackage) {
            $modulePackage->mergeAutoloads($rootPackage);
        }
    }

    private function mergeAutoloadOverrides(RootPackageInterface $rootPackage): void
    {
        foreach (glob(self::OVERRIDDEN_MODULES) as $overriddenModule) {
            $rootPackage->setAutoload(
                array_merge_recursive(
                    $rootPackage->getAutoload(),
                    ['classmap' => [$overriddenModule]]
                )
            );
        }
    }

    private function mergeRequires(RootPackageInterface $rootPackage): void
    {
        foreach ($this->modulePackageLoader->load($this->getBasePath($rootPackage)) as $modulePackage) {
            $modulePackage->mergeRequires($rootPackage);
        }
    }

    private function runAdditionalUpdateToApplyMergedConfiguration(ScriptEvent $event): void
    {
        $this->io->info('<comment>Running additional update to apply merged configuration</comment>');

        $config = $this->composer->getConfig();
        $preferSource = $config->get('preferred-install') === 'source';
        $preferDist = $config->get('preferred-install') === 'dist';

        $installer = Installer::create($this->io, Factory::create($this->io));
        $installer->setPreferSource($preferSource);
        $installer->setPreferDist($preferDist);
        $installer->setDevMode($event->isDevMode());
        $installer->setDumpAutoloader(true);
        $installer->setOptimizeAutoloader(false);
        $installer->setUpdate(true);
        $installer->run();
    }

    private function recognizePluginInstallation(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if (!($operation instanceof InstallOperation)) {
            return;
        }

        $package = $operation->getPackage()->getName();
        if ($package === 'artemeon/composer-merge-plugin') {
            $this->io->info("{$package} installed");
            $this->isFirstInstall = true;
        }
    }

    private function getBasePath(RootPackageInterface $rootPackage): string
    {
        $basePath = $rootPackage->getExtra()['base_path'] ?? null;
        if (!empty($basePath) && is_dir($basePath)) {
            return $basePath;
        } else {
            return self::MODULES_BASE_PATH;
        }
    }
}
