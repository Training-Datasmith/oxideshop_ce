<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\{Data_Mapper\Module_Configuration_Export_Data_Mapper_Interface, Data_Object\Module_Configuration};
class Class_Extensions_Export_Data_Mapper implements Module_Configuration_Export_Data_Mapper_Interface
{
    public function to_data(Module_Configuration $configuration): array
    {
        return ['classExtensions' => $this->get_class_extensions($configuration)];
    }
    private function get_class_extensions(Module_Configuration $configuration): array
    {
        $extensions = [];
        if ($configuration->has_class_extensions()) {
            $extensions['classExtension'] = [];
            foreach ($configuration->get_class_extensions() as $extension) {
                $extensions['classExtension'][] = ['shopClass' => $extension->get_shop_class_name(), 'moduleClass' => $extension->get_module_extension_class_name()];
            }
        }
        return $extensions;
    }
}