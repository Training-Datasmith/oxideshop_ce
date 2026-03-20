<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Request;

/**
 * @deprecated since v8.0.0. Use Symfony\Component\HttpFoundation\Request instead.
 */
interface Request_Interface
{
    public function get(string $key, mixed $default = null): mixed;
}