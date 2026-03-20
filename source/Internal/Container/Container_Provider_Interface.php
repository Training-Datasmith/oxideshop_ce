<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Container;

use Psr\Container\Container_Interface;
interface Container_Provider_Interface
{
    public static function get(): Container_Interface;
    public static function reset_container();
}