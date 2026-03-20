<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Unresolved_Module_Dependencies;
interface Module_Dependency_Resolver_Interface
{
    public function get_unresolved_activation_dependencies(string $module_id, int $shop_id): Unresolved_Module_Dependencies;
    public function get_unresolved_deactivation_dependencies(string $module_id, int $shop_id): Unresolved_Module_Dependencies;
}