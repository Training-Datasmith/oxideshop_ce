<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\State;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;

class ModuleStateService implements ModuleStateServiceInterface
{
    public function __construct(private readonly ModuleConfigurationDaoInterface $moduleConfigurationDao)
    {
    }

    public function isActive(string $moduleId, int $shopId): bool
    {
        return $this->moduleConfigurationDao->get($moduleId, $shopId)->isActivated();
    }
}
