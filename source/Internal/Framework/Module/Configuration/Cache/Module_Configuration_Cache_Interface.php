<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Cache;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
interface Module_Configuration_Cache_Interface
{
    public function put(int $shop_id, Module_Configuration $configuration): void;
    public function get(string $module_id, int $shop_id): Module_Configuration;
    public function exists(string $module_id, int $shop_id): bool;
    public function evict(string $module_id, int $shop_id): void;
}