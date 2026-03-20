<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Class_Extensions_Chain;
interface Active_Class_Extension_Chain_Resolver_Interface
{
    public function get_active_extension_chain(int $shop_id): Class_Extensions_Chain;
}