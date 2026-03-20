<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Mailing\Factory;

use Symfony\Component\Mailer\Transport\Transport_Interface;
interface Transport_Factory_Interface
{
    public function create(): Transport_Interface;
}