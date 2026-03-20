<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Service;

use Psr\Container\Container_Interface;
use Symfony\Component\Dependency_Injection\Container_Builder;
interface Container_Cache_Interface
{
    public function put(Container_Builder $container, int $shop_id): void;
    public function get(int $shop_id): Container_Interface;
    public function exists(int $shop_id): bool;
    public function invalidate(int $shop_id): void;
}