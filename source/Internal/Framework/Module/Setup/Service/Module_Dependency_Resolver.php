<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

use function in_array;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Dependency_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Unresolved_Module_Dependencies;
class Module_Dependency_Resolver implements Module_Dependency_Resolver_Interface
{
    public function __construct(private readonly Module_Dependency_Dao_Interface $module_dependency_dao, private readonly Module_Configuration_Dao_Interface $module_configuration_dao)
    {
    }
    public function get_unresolved_activation_dependencies(string $module_id, int $shop_id): Unresolved_Module_Dependencies
    {
        $unresolved_dependencies = new Unresolved_Module_Dependencies();
        $required_modules = $this->module_dependency_dao->get($module_id)->get_required_module_ids();
        $active_modules = $this->get_active_module_ids($shop_id);
        foreach ($required_modules as $required_module) {
            if (!in_array($required_module, $active_modules, true)) {
                $unresolved_dependencies->add_module_id($required_module);
            }
        }
        return $unresolved_dependencies;
    }
    public function get_unresolved_deactivation_dependencies(string $module_id, int $shop_id): Unresolved_Module_Dependencies
    {
        $unresolved_dependencies = new Unresolved_Module_Dependencies();
        $active_module_ids = $this->get_active_module_ids($shop_id);
        foreach ($active_module_ids as $active_module_id) {
            if ($this->is_required_by_active_module($module_id, $active_module_id)) {
                $unresolved_dependencies->add_module_id($active_module_id);
            }
        }
        return $unresolved_dependencies;
    }
    private function get_active_module_ids(int $shop_id): array
    {
        $active_module_ids = [];
        foreach ($this->module_configuration_dao->get_all($shop_id) as $module_configuration) {
            if ($module_configuration->is_activated()) {
                $active_module_ids[] = $module_configuration->get_id();
            }
        }
        return $active_module_ids;
    }
    private function is_required_by_active_module(string $module_id, string $active_module): bool
    {
        return $module_id !== $active_module && $this->module_dependency_dao->get($active_module)->is_required_module($module_id);
    }
}