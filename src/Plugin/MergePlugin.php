<?php

declare(strict_types=1);

namespace Artemeon\Composer\Plugin;

use Artemeon\Composer\Module\ModulePackageLoader;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;

final class MergePlugin implements PluginInterface, EventSubscriberInterface
{
    private const CALLBACK_PRIORITY = 50000;
    private const MODULES_BASE_PATH = '../core';

    private Composer $composer;
    private IOInterface $io;
    private ModulePackageLoader $modulePackageLoader;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->modulePackageLoader = new ModulePackageLoader($io);
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
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['preAutoloadDump', static::CALLBACK_PRIORITY],
        ];
    }
    public function preAutoloadDump(ScriptEvent $event): void
    {
        $rootPackage = $this->composer->getPackage();
        $this->mergeAutoloads($rootPackage);
    }

    private function mergeAutoloads(RootPackageInterface $rootPackage): void
    {
        foreach ($this->modulePackageLoader->load(self::MODULES_BASE_PATH) as $modulePackage) {
            $modulePackage->mergeAutoloads($rootPackage);
        }
    }
}
