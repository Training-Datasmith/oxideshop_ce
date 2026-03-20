<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Cache;

interface Shop_Template_Cache_Service_Interface
{
    public function get_cache_directory(int $shop_id): string;
    public function invalidate_cache(int $shop_id): void;
    public function invalidate_all_shops_cache(): void;
}