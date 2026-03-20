<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Path;
class Module_Assets_Path_Resolver implements Module_Assets_Path_Resolver_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context)
    {
    }
    public function get_assets_path(string $module_id): string
    {
        return Path::join($this->context->get_out_path(), 'modules', $module_id);
    }
}