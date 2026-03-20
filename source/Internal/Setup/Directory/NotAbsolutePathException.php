<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Directory;

use Exception;
class Not_Absolute_Path_Exception extends Exception
{
    public const NOT_ABSOLUTE_PATHS = 'shop-directory and compile-directory must be absolute paths';
}