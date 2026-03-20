<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception;

use function sprintf;
class Invalid_Rights_Exception extends \Exception
{
    public function __construct(string $right)
    {
        parent::__construct(sprintf('Provided right %s is not a valid shop right.', $right));
    }
}