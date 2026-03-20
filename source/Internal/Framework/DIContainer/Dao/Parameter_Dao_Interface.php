<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Dao;

interface Parameter_Dao_Interface
{
    public function add(string $name, array|bool|string|int|float|\Unit_Enum|null $value, int $shop_id): void;
    public function remove(string $name, int $shop_id): void;
    public function has(string $name, int $shop_id): bool;
}