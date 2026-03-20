<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Rate_Limiter;

use Symfony\Component\Rate_Limiter\Limiter_Interface;
interface Api_Rate_Limiter_Factory_Interface
{
    public function create(string $client_identifier): Limiter_Interface;
}