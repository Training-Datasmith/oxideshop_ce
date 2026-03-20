<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Rate_Limiter;

use Symfony\Component\Http_Foundation\Request;
interface Client_Identifier_Provider_Interface
{
    public function get_client_identifier(Request $request): string;
}