<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\State;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
class Module_State_Service implements Module_State_Service_Interface
{
    public function __construct(private readonly Module_Configuration_Dao_Interface $module_configuration_dao)
    {
    }
    public function is_active(string $module_id, int $shop_id): bool
    {
        return $this->module_configuration_dao->get($module_id, $shop_id)->is_activated();
    }
}