<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception;

use function sprintf;
class Invalid_Shop_Exception extends \Exception
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Provided shopId %d is not a valid shop id.', $id));
    }
}