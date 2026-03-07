<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ExtensionNotInChainException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;

class ModuleConfigurationMergingService implements ModuleConfigurationMergingServiceInterface
{
    public function __construct(
        private readonly SettingsMergingServiceInterface $settingsMergingService,
        private readonly ModuleClassExtensionsMergingServiceInterface $classExtensionsMergingService
    ) {
    }

    /**
     * @inheritDoc
     * @throws ModuleConfigurationNotFoundException
     * @throws ExtensionNotInChainException
     */
    public function merge(
        ShopConfiguration $shopConfiguration,
        ModuleConfiguration $moduleConfiguration
    ): ShopConfiguration {
        $moduleConfigurationClone = $this->cloneModuleConfiguration($moduleConfiguration);

        $mergedClassExtensionChain = $this->classExtensionsMergingService->merge(
            $shopConfiguration,
            $moduleConfigurationClone
        );
        $shopConfiguration->setClassExtensionsChain($mergedClassExtensionChain);

        $mergedModuleConfiguration = $this->settingsMergingService->merge(
            $shopConfiguration,
            $moduleConfigurationClone
        );

        $this->setActivatedOptionToMergedConfiguration($shopConfiguration, $mergedModuleConfiguration);

        $shopConfiguration->addModuleConfiguration($mergedModuleConfiguration);

        return $shopConfiguration;
    }

    private function cloneModuleConfiguration(ModuleConfiguration $moduleConfiguration): ModuleConfiguration
    {
        $moduleSettingClones = [];
        foreach ($moduleConfiguration->getModuleSettings() as $moduleSetting) {
            $moduleSettingClones[] = clone $moduleSetting;
        }
        $moduleConfigurationClone = clone $moduleConfiguration;
        $moduleConfigurationClone->setModuleSettings($moduleSettingClones);
        return $moduleConfigurationClone;
    }

    /**
     * @throws ModuleConfigurationNotFoundException
     */
    private function setActivatedOptionToMergedConfiguration(
        ShopConfiguration $shopConfiguration,
        ModuleConfiguration $mergedModuleConfiguration
    ): void {
        if ($shopConfiguration->hasModuleConfiguration($mergedModuleConfiguration->getId())) {
            $isConfigured = $shopConfiguration
                ->getModuleConfiguration($mergedModuleConfiguration->getId())
                ->isActivated();
            $mergedModuleConfiguration->setActivated($isConfigured);
        }
    }
}
