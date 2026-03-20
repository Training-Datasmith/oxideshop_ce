<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Cache;

interface Shop_Cache_Cleaner_Interface
{
    public function clear(int $shop_id): void;
    public function clear_all(): void;
}