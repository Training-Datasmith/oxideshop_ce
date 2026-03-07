<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service;

interface ModuleActivationServiceInterface
{
    public function activate(string $moduleId, int $shopId);

    public function deactivate(string $moduleId, int $shopId);
}
