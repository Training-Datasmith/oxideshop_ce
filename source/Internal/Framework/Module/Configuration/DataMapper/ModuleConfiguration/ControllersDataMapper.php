<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Controller;
class Controllers_Data_Mapper implements Module_Configuration_Data_Mapper_Interface
{
    public const MAPPING_KEY = 'controllers';
    public function to_data(Module_Configuration $configuration): array
    {
        $data = [];
        if ($configuration->has_controllers()) {
            $data[self::MAPPING_KEY] = $this->get_controllers($configuration);
        }
        return $data;
    }
    public function from_data(Module_Configuration $module_configuration, array $data): Module_Configuration
    {
        if (isset($data[self::MAPPING_KEY])) {
            $this->set_controllers($module_configuration, $data[self::MAPPING_KEY]);
        }
        return $module_configuration;
    }
    private function set_controllers(Module_Configuration $module_configuration, array $controllers): void
    {
        foreach ($controllers as $id => $controller_class_namespace) {
            $module_configuration->add_controller(new Controller($id, $controller_class_namespace));
        }
    }
    private function get_controllers(Module_Configuration $configuration): array
    {
        $controllers = [];
        if ($configuration->has_controllers()) {
            foreach ($configuration->get_controllers() as $controller) {
                $controllers[$controller->get_id()] = $controller->get_controller_class_name_space();
            }
        }
        return $controllers;
    }
}