<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;

class Event
{
    public function __construct(private readonly string $action, private readonly string $method)
    {
    }
    public function get_action(): string
    {
        return $this->action;
    }
    public function get_method(): string
    {
        return $this->method;
    }
}