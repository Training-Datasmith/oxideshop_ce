<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Exception;

class Empty_Product_Media_Role_Exception extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('ProductMediaRole must not be empty');
    }
}