<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Event;

use Symfony\Contracts\Event_Dispatcher\Event;
class Clear_Shop_Cache_Event extends Event
{
    public function __construct(private readonly int $shop_id)
    {
    }
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
}