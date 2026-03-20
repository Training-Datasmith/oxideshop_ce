<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Bridge;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface Admin_Theme_Bridge_Interface
{
    public function get_active_theme(): string;
}