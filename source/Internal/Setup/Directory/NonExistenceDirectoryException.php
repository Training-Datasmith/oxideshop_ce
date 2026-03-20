<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Directory;

use Exception;
class Non_Existence_Directory_Exception extends Exception
{
    public const NON_EXISTENCE_DIRECTORY = 'Following folder does not exist';
}