<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Rate_Limiter;

use Symfony\Component\Http_Foundation\Request;
readonly class Client_Identifier_Provider implements Client_Identifier_Provider_Interface
{
    public function get_client_identifier(Request $request): string
    {
        return $request->get_client_ip() ?? 'unknown';
    }
}