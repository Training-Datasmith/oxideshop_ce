<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception;

use function sprintf;
class Invalid_Email_Exception extends \Exception
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Provided email string %s is not a valid email.', $email));
    }
}