<?php

/**Utility
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Class_Extensions_Chain;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\State\Module_State_Service_Interface;
class Active_Class_Extension_Chain_Resolver implements Active_Class_Extension_Chain_Resolver_Interface
{
    public function __construct(private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao, private readonly Module_State_Service_Interface $module_state_service)
    {
    }
    public function get_active_extension_chain(int $shop_id): Class_Extensions_Chain
    {
        $shop_configuration = $this->shop_configuration_dao->get($shop_id);
        $class_extension_chain = $shop_configuration->get_class_extensions_chain();
        $active_extensions = [];
        foreach ($class_extension_chain as $shop_class => $module_extension_classes) {
            $active_module_extension_classes = $this->get_active_module_extension_classes($module_extension_classes, $shop_id, $shop_configuration);
            if (!empty($active_module_extension_classes)) {
                $active_extensions[$shop_class] = $active_module_extension_classes;
            }
        }
        $active_extension_chain = new Class_Extensions_Chain();
        $active_extension_chain->set_chain($active_extensions);
        return $active_extension_chain;
    }
    private function get_active_module_extension_classes(array $module_extension_classes, int $shop_id, Shop_Configuration $shop_configuration): array
    {
        $active_classes = [];
        foreach ($module_extension_classes as $extension_class) {
            if ($this->is_active_extension($extension_class, $shop_id, $shop_configuration)) {
                $active_classes[] = $extension_class;
            }
        }
        return $active_classes;
    }
    private function is_active_extension(string $class_extension, int $shop_id, Shop_Configuration $shop_configuration): bool
    {
        foreach ($shop_configuration->get_module_configurations() as $module_configuration) {
            if ($module_configuration->has_class_extension($class_extension) && $this->module_state_service->is_active($module_configuration->get_id(), $shop_id)) {
                return true;
            }
        }
        return false;
    }
}