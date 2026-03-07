<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

class ShopVersion
{
    /**
     * @return string OXID eShop compilation version.
     */
    public static function getVersion(): string
    {
        return '8.0.0-alpha.3';
    }
}
