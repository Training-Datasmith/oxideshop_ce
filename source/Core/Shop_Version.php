<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

class Shop_Version
{
    /**
     * @return string OXID eShop compilation version.
     */
    public static function get_version(): string
    {
        return '8.0.0-alpha.3';
    }
}