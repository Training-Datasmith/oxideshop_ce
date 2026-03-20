<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class is responsible for openSSL functionality availability checking.
 */
class Open_Ssl_Functionality_Checker
{
    /**
     * Checks if openssl_random_pseudo_bytes function is available.
     */
    public function is_open_ssl_random_bytes_generator_available(): bool
    {
        return function_exists('openssl_random_pseudo_bytes');
    }
}