<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path;

interface Module_Assets_Path_Resolver_Bridge_Interface
{
    public function get_assets_path(string $module_id): string;
}