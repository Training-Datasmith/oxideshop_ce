<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Bridge;

interface Newsletter_Recipients_Dao_Bridge_Interface
{
    public function get(int $shop_id): array;
}