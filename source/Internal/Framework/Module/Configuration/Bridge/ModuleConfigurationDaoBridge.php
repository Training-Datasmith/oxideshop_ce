<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleEnvironmentConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class ModuleConfigurationDaoBridge implements ModuleConfigurationDaoBridgeInterface
{
    public function __construct(
        private readonly ContextInterface $context,
        private readonly ModuleConfigurationDaoInterface $moduleConfigurationDao,
        private readonly ModuleEnvironmentConfigurationDaoInterface $moduleEnvironmentConfigurationDao
    ) {
    }

    public function get(string $moduleId): ModuleConfiguration
    {
        return $this->moduleConfigurationDao->get($moduleId, $this->context->getCurrentShopId());
    }

    public function save(ModuleConfiguration $moduleConfiguration): void
    {
        $this->moduleConfigurationDao->save($moduleConfiguration, $this->context->getCurrentShopId());
        $this->moduleEnvironmentConfigurationDao->remove(
            $moduleConfiguration->getId(),
            $this->context->getCurrentShopId()
        );
    }
}
