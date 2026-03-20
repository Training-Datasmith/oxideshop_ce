<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
interface Module_Configuration_Dao_Interface
{
    public function get(string $module_id, int $shop_id): Module_Configuration;
    public function save(Module_Configuration $module_configuration, int $shop_id);
    public function delete(string $module_id, int $shop_id): void;
    /**
     * @return ModuleConfiguration[]
     */
    public function get_all(int $shop_id): array;
    /**
     * @deprecated will be completely removed
     */
    public function delete_all(int $shop_id): void;
    public function exists(string $module_id, int $shop_id): bool;
}