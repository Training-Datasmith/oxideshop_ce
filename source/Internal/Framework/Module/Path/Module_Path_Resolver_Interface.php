<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path;

interface Module_Path_Resolver_Interface
{
    public function get_full_module_path_from_configuration(string $module_id, int $shop_id): string;
}