<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface Shop_Configuration_Dao_Bridge_Interface
{
    public function get(): Shop_Configuration;
    /**
     * @deprecated use ModuleConfigurationDaoInterface::save() and ClassExtensionsChainDaoInterface::saveChain() instead
     */
    public function save(Shop_Configuration $shop_configuration);
}