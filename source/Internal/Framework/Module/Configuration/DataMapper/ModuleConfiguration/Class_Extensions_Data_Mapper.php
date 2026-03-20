<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Class_Extension;
class Class_Extensions_Data_Mapper implements Module_Configuration_Data_Mapper_Interface
{
    public const MAPPING_KEY = 'classExtensions';
    public function to_data(Module_Configuration $configuration): array
    {
        $data = [];
        if ($configuration->has_class_extensions()) {
            $data[self::MAPPING_KEY] = $this->get_class_extensions($configuration);
        }
        return $data;
    }
    public function from_data(Module_Configuration $module_configuration, array $data): Module_Configuration
    {
        if (isset($data[self::MAPPING_KEY])) {
            $this->set_class_extensions($module_configuration, $data[self::MAPPING_KEY]);
        }
        return $module_configuration;
    }
    private function get_class_extensions(Module_Configuration $configuration): array
    {
        $extensions = [];
        foreach ($configuration->get_class_extensions() as $extension) {
            $extensions[$extension->get_shop_class_name()] = $extension->get_module_extension_class_name();
        }
        return $extensions;
    }
    private function set_class_extensions(Module_Configuration $module_configuration, array $extensions): void
    {
        foreach ($extensions as $shop_class => $module_class) {
            $module_configuration->add_class_extension(new Class_Extension($shop_class, $module_class));
        }
    }
}