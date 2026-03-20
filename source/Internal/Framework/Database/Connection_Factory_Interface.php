<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database;

use Doctrine\DBAL\Connection;
interface Connection_Factory_Interface
{
    public function create(): Connection;
}