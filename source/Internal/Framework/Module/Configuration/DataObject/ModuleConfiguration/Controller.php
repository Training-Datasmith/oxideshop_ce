<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;

class Controller
{
    public function __construct(private readonly string $id, private readonly string $controller_class_name_space)
    {
    }
    public function get_id(): string
    {
        return $this->id;
    }
    public function get_controller_class_name_space(): string
    {
        return $this->controller_class_name_space;
    }
}