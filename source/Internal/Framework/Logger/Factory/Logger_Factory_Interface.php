<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Logger\Factory;

use Psr\Log\Logger_Interface;
interface Logger_Factory_Interface
{
    public function create(): Logger_Interface;
}