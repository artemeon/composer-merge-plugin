<?php

declare(strict_types=1);

namespace Artemeon\Composer\Plugin;

use Artemeon\Composer\Module\ModulePackageLoader;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

final class MergePlugin implements PluginInterface
{
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
}
