<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

interface Module_Environment_Configuration_Dao_Interface
{
    public function get(string $module_id, int $shop_id): array;
    public function remove(string $module_id, int $shop_id): void;
}