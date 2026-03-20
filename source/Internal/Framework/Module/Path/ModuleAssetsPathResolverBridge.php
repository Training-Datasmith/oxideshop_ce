<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path;

class Module_Assets_Path_Resolver_Bridge implements Module_Assets_Path_Resolver_Interface
{
    public function __construct(private readonly Module_Assets_Path_Resolver_Interface $module_assets_path_resolver)
    {
    }
    public function get_assets_path(string $module_id): string
    {
        return $this->module_assets_path_resolver->get_assets_path($module_id);
    }
}