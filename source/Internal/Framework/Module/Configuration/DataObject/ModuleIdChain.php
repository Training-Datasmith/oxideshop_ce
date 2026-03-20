<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object;

use ArrayIterator;
use IteratorAggregate;
class Module_Id_Chain implements IteratorAggregate
{
    public function __construct(private readonly array $module_ids)
    {
    }
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->module_ids);
    }
}