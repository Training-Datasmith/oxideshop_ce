<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Dependencies;
interface Module_Dependency_Dao_Interface
{
    public function get(string $module_id): Module_Dependencies;
}