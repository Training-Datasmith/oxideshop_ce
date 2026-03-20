<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
class Shop_Configuration_Dao_Bridge implements Shop_Configuration_Dao_Bridge_Interface
{
    public function __construct(private readonly Context_Interface $context, private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao)
    {
    }
    public function get(): Shop_Configuration
    {
        return $this->shop_configuration_dao->get($this->context->get_current_shop_id());
    }
    public function save(Shop_Configuration $shop_configuration): void
    {
        $this->shop_configuration_dao->save($shop_configuration, $this->context->get_current_shop_id());
    }
}