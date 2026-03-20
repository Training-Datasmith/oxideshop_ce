<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\State;

interface Module_State_Service_Interface
{
    public function is_active(string $module_id, int $shop_id): bool;
}