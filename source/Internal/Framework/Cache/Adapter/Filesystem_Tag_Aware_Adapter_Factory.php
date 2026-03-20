<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Adapter;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Cache\Adapter\Filesystem_Tag_Aware_Adapter;
use Symfony\Component\Cache\Adapter\Tag_Aware_Adapter_Interface;
use Symfony\Component\Filesystem\Path;
class Filesystem_Tag_Aware_Adapter_Factory implements Tag_Aware_Adapter_Factory_Interface
{
    public function __construct(private readonly Context_Interface $context)
    {
    }
    public function create(int $shop_id): Tag_Aware_Adapter_Interface
    {
        return new Filesystem_Tag_Aware_Adapter(namespace: "cache_items_shop_{$shop_id}", directory: Path::join($this->context->get_cache_directory(), 'pool'));
    }
}