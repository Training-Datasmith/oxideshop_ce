<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

class ProjectConfigurationGenerator implements ProjectConfigurationGeneratorInterface
{
    public function __construct(
        private readonly ShopConfigurationDaoInterface $shopConfigurationDao,
        private readonly BasicContextInterface $context
    ) {
    }

    /**
     * Generates default project configuration.
     */
    public function generate(): void
    {
        $allShopIds = $this->context->getAllShopIds();
        $this->shopConfigurationDao->deleteAll();
        foreach ($allShopIds as $shopId) {
            $this->shopConfigurationDao->save(new ShopConfiguration(), $shopId);
        }
    }
}
