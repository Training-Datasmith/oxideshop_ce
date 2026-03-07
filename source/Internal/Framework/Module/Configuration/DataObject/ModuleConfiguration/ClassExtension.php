<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

class ClassExtension
{
    public function __construct(
        private readonly string $ShopClassName,
        private readonly string $moduleExtensionClassName
    ) {
    }

    public function getShopClassName(): string
    {
        return $this->ShopClassName;
    }

    public function getModuleExtensionClassName(): string
    {
        return $this->moduleExtensionClassName;
    }
}
