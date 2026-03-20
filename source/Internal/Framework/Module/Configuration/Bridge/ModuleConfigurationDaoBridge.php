<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Environment_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
class Module_Configuration_Dao_Bridge implements Module_Configuration_Dao_Bridge_Interface
{
    public function __construct(private readonly Context_Interface $context, private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Module_Environment_Configuration_Dao_Interface $module_environment_configuration_dao)
    {
    }
    public function get(string $module_id): Module_Configuration
    {
        return $this->module_configuration_dao->get($module_id, $this->context->get_current_shop_id());
    }
    public function save(Module_Configuration $module_configuration): void
    {
        $this->module_configuration_dao->save($module_configuration, $this->context->get_current_shop_id());
        $this->module_environment_configuration_dao->remove($module_configuration->get_id(), $this->context->get_current_shop_id());
    }
}