<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;

class Class_Extension
{
    public function __construct(private readonly string $shop_class_name, private readonly string $module_extension_class_name)
    {
    }
    public function get_shop_class_name(): string
    {
        return $this->shop_class_name;
    }
    public function get_module_extension_class_name(): string
    {
        return $this->module_extension_class_name;
    }
}