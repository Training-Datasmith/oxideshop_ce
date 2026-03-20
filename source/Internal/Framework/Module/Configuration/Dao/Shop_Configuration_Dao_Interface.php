<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
interface Shop_Configuration_Dao_Interface
{
    public function get(int $shop_id): Shop_Configuration;
    /**
     * @deprecated use ModuleConfigurationDaoInterface::save() and ClassExtensionsChainDaoInterface::saveChain() instead
     */
    public function save(Shop_Configuration $shop_configuration, int $shop_id): void;
    /**
     * @return ShopConfiguration[]
     */
    public function get_all(): array;
    /**
     * @deprecated will be completely removed
     */
    public function delete_all(): void;
}