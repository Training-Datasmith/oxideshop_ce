<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Path_Resolver_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
class Modules_Data_Provider implements Modules_Data_Provider_Interface
{
    public function __construct(private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao, private readonly Module_Path_Resolver_Interface $module_path_resolver, private readonly Context_Interface $context)
    {
    }
    /** @inheritDoc */
    public function get_module_ids(): array
    {
        $module_ids = [];
        $shop_id = $this->context->get_current_shop_id();
        $module_configurations = $this->shop_configuration_dao->get($shop_id)->get_module_configurations();
        foreach ($module_configurations as $module_configuration) {
            $module_ids[] = $module_configuration->get_id();
        }
        return $module_ids;
    }
    /** @inheritDoc */
    public function get_module_paths(): array
    {
        $shop_id = $this->context->get_current_shop_id();
        $module_paths = [];
        $module_configurations = $this->shop_configuration_dao->get($shop_id)->get_module_configurations();
        foreach ($module_configurations as $module_configuration) {
            $module_paths[] = $this->module_path_resolver->get_full_module_path_from_configuration($module_configuration->get_id(), $this->context->get_current_shop_id());
        }
        return $module_paths;
    }
}