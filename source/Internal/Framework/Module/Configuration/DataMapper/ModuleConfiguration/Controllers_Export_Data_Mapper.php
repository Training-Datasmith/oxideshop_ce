<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\{Data_Mapper\Module_Configuration_Export_Data_Mapper_Interface, Data_Object\Module_Configuration};
class Controllers_Export_Data_Mapper implements Module_Configuration_Export_Data_Mapper_Interface
{
    public function to_data(Module_Configuration $configuration): array
    {
        return ['controllers' => $this->get_controllers($configuration)];
    }
    private function get_controllers(Module_Configuration $configuration): array
    {
        $controllers = [];
        if ($configuration->has_controllers()) {
            $controllers['controller'] = [];
            foreach ($configuration->get_controllers() as $controller) {
                $controllers['controller'][] = ['id' => $controller->get_id(), 'controllerClassNameSpace' => $controller->get_controller_class_name_space()];
            }
        }
        return $controllers;
    }
}