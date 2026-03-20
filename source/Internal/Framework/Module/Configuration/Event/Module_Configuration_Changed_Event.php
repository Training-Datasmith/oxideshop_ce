<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Event;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Symfony\Contracts\Event_Dispatcher\Event;
class Module_Configuration_Changed_Event extends Event
{
    public function __construct(private readonly Module_Configuration $module_configuration, private readonly int $shop_id)
    {
    }
    public function get_module_configuration(): Module_Configuration
    {
        return $this->module_configuration;
    }
    public function get_module_id(): string
    {
        return $this->module_configuration->get_id();
    }
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
}