<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Routing;

use Oxid_Esales\Eshop\Core\Contract\Class_Name_Resolver_Interface;
use Oxid_Esales\Eshop\Core\Contract\Controller_Map_Provider_Interface;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade\Active_Modules_Data_Provider_Bridge_Interface;
/**
 * This class maps controller ID to controller class name and vice versa.
 *
 * @internal Do not make a module extension for this class.
 */
class Controller_Class_Name_Resolver implements Class_Name_Resolver_Interface
{
    public function __construct(private ?Controller_Map_Provider_Interface $shop_controller_map_provider = null, private ?Active_Modules_Data_Provider_Bridge_Interface $active_modules_data_provider = null)
    {
    }
    /**
     * Map argument classId to related className.
     *
     * @param string $classId
     *
     * @return string|null
     */
    public function get_class_name_by_id($class_id)
    {
        return ($this->get_class_name_from_container_parameters_map($class_id) ?: $this->get_class_name_from_shop_map($class_id)) ?: $this->get_class_name_from_module_map($class_id);
    }
    /**
     * Map argument className to related classId.
     *
     * @param string $className
     *
     * @return string|null
     */
    public function get_id_by_class_name($class_name)
    {
        return ($this->get_class_id_from_container_parameters_map($class_name) ?: $this->get_class_id_from_shop_map($class_name)) ?: $this->get_class_id_from_module_map($class_name);
    }
    /**
     * Get class name from shop controller provider.
     *
     * @param string $classId
     *
     * @return string|null
     */
    protected function get_class_name_from_shop_map($class_id)
    {
        $id_to_name_map = $this->get_shop_controller_map_provider()->get_controller_map();
        return $this->array_lookup($class_id, $id_to_name_map);
    }
    /**
     * Get class name from module controller provider.
     *
     * @param string $classId
     *
     * @return string|null
     */
    protected function get_class_name_from_module_map($class_id)
    {
        $controllers_array = [];
        foreach ($this->get_active_modules_data_provider()->get_controllers() as $controller) {
            $controllers_array[$controller->get_id()] = $controller->get_controller_class_name_space();
        }
        return $this->array_lookup($class_id, $controllers_array);
    }
    /**
     * Get class id from shop controller provider.
     *
     * @param string $className
     *
     * @return string|null
     */
    protected function get_class_id_from_shop_map($class_name)
    {
        $id_to_name_map = $this->get_shop_controller_map_provider()->get_controller_map();
        return $this->array_lookup($class_name, array_flip($id_to_name_map));
    }
    /**
     * Get class id from module controller provider.
     *
     * @param string $className
     *
     * @return string|null
     */
    protected function get_class_id_from_module_map($class_name)
    {
        $controllers_array = [];
        foreach ($this->get_active_modules_data_provider()->get_controllers() as $controller) {
            $controllers_array[$controller->get_id()] = $controller->get_controller_class_name_space();
        }
        return $this->array_lookup($class_name, array_flip($controllers_array));
    }
    /**
     * @param string $key
     * @param array  $keys2Values
     *
     * @return string|null
     */
    protected function array_lookup($key, $keys2Values)
    {
        $keys2Values = array_change_key_case($keys2Values);
        $key = strtolower($key);
        return $keys2Values[$key] ?? null;
    }
    /**
     * Getter for ShopControllerMapProvider object
     *
     * @return \OxidEsales\Eshop\Core\Routing\ShopControllerMapProvider
     */
    protected function get_shop_controller_map_provider(): ?\Oxid_Esales\Eshop\Core\Contract\Controller_Map_Provider_Interface
    {
        if ($this->shop_controller_map_provider === null) {
            $this->shop_controller_map_provider = ox_new(\Oxid_Esales\Eshop\Core\Routing\Shop_Controller_Map_Provider::class);
        }
        return $this->shop_controller_map_provider;
    }
    private function get_active_modules_data_provider(): Active_Modules_Data_Provider_Bridge_Interface
    {
        if ($this->active_modules_data_provider === null) {
            $this->active_modules_data_provider = Container_Facade::get(Active_Modules_Data_Provider_Bridge_Interface::class);
        }
        return $this->active_modules_data_provider;
    }
    private function get_class_name_from_container_parameters_map(string $class_id): ?string
    {
        return Container_Facade::get_parameter('oxid.view_controllers_map')[$class_id] ?? null;
    }
    private function get_class_id_from_container_parameters_map(string $class_name): false|string
    {
        return array_search($class_name, Container_Facade::get_parameter('oxid.view_controllers_map'), true);
    }
}