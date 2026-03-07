<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Translation\Locator;

// phpcs:disable
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModulesDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Translation\Locator\AdminAreaModuleTranslationFileLocatorInterface as LocatorInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Translation\Locator\ModuleTranslationFileLocatorAbstract as LocatorAbstract;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

// phpcs:enable

class AdminAreaModuleTranslationFileLocator extends LocatorAbstract implements LocatorInterface
{
    public function __construct(
        private readonly ModulesDataProviderInterface $modulesDataProvider,
        private readonly Filesystem $filesystem,
        private readonly string $adminThemeName
    ) {
    }

    public function locate(string $lang): array
    {
        $langFiles = [];

        foreach ($this->modulesDataProvider->getModulePaths() as $moduleLangPath) {
            $moduleLangPath = Path::join(
                $this->checkAndAddApplicationFolder($this->filesystem, $moduleLangPath),
                'views',
                $this->adminThemeName,
                $lang
            );

            $langFiles = $this->appendLangFiles($langFiles, $moduleLangPath);

            $langFiles = $this->appendModuleOptionsFile($langFiles, $moduleLangPath);
        }

        return $langFiles;
    }

    private function appendModuleOptionsFile(array $langFiles, string $moduleLangPath): array
    {
        $langFilePath = Path::join($moduleLangPath, 'module_options.php');

        if ($this->filesystem->exists($langFilePath)) {
            $langFiles[] = $langFilePath;
        }

        return $langFiles;
    }
}
