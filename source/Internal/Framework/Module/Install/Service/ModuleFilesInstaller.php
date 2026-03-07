<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModuleAssetsPathResolverInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ModuleFilesInstaller implements ModuleFilesInstallerInterface
{
    public function __construct(
        private readonly Filesystem $fileSystemService,
        private readonly ModuleConfigurationDaoInterface $moduleConfigurationDao,
        private readonly ModuleAssetsPathResolverInterface $moduleAssetsPathResolver
    ) {
    }

    public function install(OxidEshopPackage $package): void
    {
        $symlinkFile = $this->getModuleAssetsPath($package);
        $modulesAssetsDirectory = Path::getDirectory($symlinkFile);
        $relativePathToPackageAssets = Path::makeRelative(
            Path::join($package->getPackagePath(), '/assets'),
            $modulesAssetsDirectory
        );
        $this->fileSystemService->symlink(
            $relativePathToPackageAssets,
            $symlinkFile,
            true
        );
    }

    public function uninstall(OxidEshopPackage $package): void
    {
        $this->fileSystemService->remove($this->getModuleAssetsPath($package));
    }

    public function isInstalled(OxidEshopPackage $package): bool
    {
        return is_link($this->getModuleAssetsPath($package));
    }

    private function getModuleAssetsPath(OxidEshopPackage $package): string
    {
        return $this->moduleAssetsPathResolver->getAssetsPath($this->getModuleId($package));
    }

    private function getModuleId(OxidEshopPackage $package): string
    {
        return $this
            ->moduleConfigurationDao
            ->get($package->getPackagePath())
            ->getId();
    }
}
