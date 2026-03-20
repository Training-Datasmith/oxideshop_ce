<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Chain;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Class_Extensions_Chain;
interface Class_Extensions_Chain_Dao_Interface
{
    public function get_chain(int $shop_id): Class_Extensions_Chain;
    public function save_chain(int $shop_id, Class_Extensions_Chain $chain): void;
}