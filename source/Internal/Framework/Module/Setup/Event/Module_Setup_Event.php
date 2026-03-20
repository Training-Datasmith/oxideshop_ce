<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event;

use Symfony\Contracts\Event_Dispatcher\Event;
abstract class Module_Setup_Event extends Event
{
    public function __construct(private readonly int $shop_id, private readonly string $module_id)
    {
    }
    public function get_module_id(): string
    {
        return $this->module_id;
    }
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
}