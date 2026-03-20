<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Adapter;

use Symfony\Component\Cache\Adapter\Tag_Aware_Adapter_Interface;
interface Tag_Aware_Adapter_Factory_Interface
{
    public function create(int $shop_id): Tag_Aware_Adapter_Interface;
}