<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;

class BootstrapModuleInstaller implements ModuleInstallerInterface
{
    public function __construct(
        private readonly ModuleFilesInstallerInterface $moduleFilesInstaller,
        private readonly ModuleConfigurationInstallerInterface $moduleConfigurationInstaller
    ) {
    }

    public function install(OxidEshopPackage $package): void
    {
        $this->moduleFilesInstaller->install($package);
        $this->moduleConfigurationInstaller->install($package->getPackagePath());
    }

    public function uninstall(OxidEshopPackage $package): void
    {
        $this->moduleConfigurationInstaller->uninstall($package->getPackagePath());
        $this->moduleFilesInstaller->uninstall($package);
    }

    public function isInstalled(OxidEshopPackage $package): bool
    {
        return $this->moduleFilesInstaller->isInstalled($package)
               && $this->moduleConfigurationInstaller->isInstalled($package->getPackagePath());
    }
}
