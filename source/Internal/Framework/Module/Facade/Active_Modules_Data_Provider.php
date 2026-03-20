<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Cache\Module_Cache_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Path_Resolver_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service\Active_Class_Extension_Chain_Resolver_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
class Active_Modules_Data_Provider implements Active_Modules_Data_Provider_Interface
{
    public function __construct(private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Module_Path_Resolver_Interface $module_path_resolver, private readonly Context_Interface $context, private readonly Module_Cache_Interface $module_cache, private readonly Active_Class_Extension_Chain_Resolver_Interface $active_class_extension_chain_resolver)
    {
    }
    /** @inheritDoc */
    public function get_module_ids(): array
    {
        $cache_key = 'active_module_ids';
        if (!$this->module_cache->exists($cache_key)) {
            $module_ids = [];
            foreach ($this->get_active_module_configurations() as $module_configuration) {
                $module_ids[] = $module_configuration->get_id();
            }
            $this->module_cache->put($cache_key, $module_ids);
        }
        return $this->module_cache->get($cache_key);
    }
    /** @inheritDoc */
    public function get_module_paths(): array
    {
        $cache_key = 'absolute_module_paths';
        if (!$this->module_cache->exists($cache_key)) {
            $this->module_cache->put($cache_key, $this->collect_module_paths_for_caching());
        }
        return $this->module_cache->get($cache_key);
    }
    /** @inheritDoc */
    public function get_controllers(): array
    {
        $cache_key = 'controllers';
        if (!$this->module_cache->exists($cache_key)) {
            $this->module_cache->put($cache_key, $this->collect_controllers_for_caching());
        }
        return $this->create_controllers_from_data($this->module_cache->get($cache_key));
    }
    /** @inheritDoc */
    public function get_class_extensions(): array
    {
        $shop_id = $this->context->get_current_shop_id();
        $cache_key = 'module_class_extensions';
        if (!$this->module_cache->exists($cache_key)) {
            $this->module_cache->put($cache_key, $this->active_class_extension_chain_resolver->get_active_extension_chain($shop_id)->get_chain());
        }
        return $this->module_cache->get($cache_key);
    }
    private function collect_module_paths_for_caching(): array
    {
        $module_paths = [];
        foreach ($this->get_active_module_configurations() as $module_configuration) {
            $module_paths[$module_configuration->get_id()] = $this->module_path_resolver->get_full_module_path_from_configuration($module_configuration->get_id(), $this->context->get_current_shop_id());
        }
        return $module_paths;
    }
    private function collect_controllers_for_caching(): array
    {
        $controllers = [];
        foreach ($this->get_active_module_configurations() as $module_configuration) {
            foreach ($module_configuration->get_controllers() as $controller) {
                $controllers[$controller->get_id()] = $controller->get_controller_class_name_space();
            }
        }
        return $controllers;
    }
    /** @return ModuleConfiguration[] */
    private function get_active_module_configurations(): array
    {
        $module_configurations = [];
        $shop_id = $this->context->get_current_shop_id();
        foreach ($this->module_configuration_dao->get_all($shop_id) as $module_configuration) {
            if ($module_configuration->is_activated()) {
                $module_configurations[] = $module_configuration;
            }
        }
        return $module_configurations;
    }
    private function create_controllers_from_data(array $data): array
    {
        $controllers = [];
        foreach ($data as $id => $namespace) {
            $controllers[] = new Module_Configuration\Controller($id, $namespace);
        }
        return $controllers;
    }
}