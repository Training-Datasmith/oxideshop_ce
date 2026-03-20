<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Mailing\Factory;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Transport_Interface;
readonly class Transport_Factory implements Transport_Factory_Interface
{
    public function __construct(private string $dsn)
    {
    }
    public function create(): Transport_Interface
    {
        return Transport::from_dsn($this->dsn);
    }
}