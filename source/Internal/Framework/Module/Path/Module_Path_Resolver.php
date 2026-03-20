<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Path;
class Module_Path_Resolver implements Module_Path_Resolver_Interface
{
    public function __construct(private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Basic_Context_Interface $context)
    {
    }
    /**
     * This method does not validate if the path returned exists. It returns more or less the value from the project
     * configuration.
     *
     *
     */
    public function get_full_module_path_from_configuration(string $module_id, int $shop_id): string
    {
        $module_configuration = $this->module_configuration_dao->get($module_id, $shop_id);
        return Path::join($this->context->get_shop_root_path(), $module_configuration->get_module_source());
    }
}