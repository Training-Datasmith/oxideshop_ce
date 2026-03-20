<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Database;

use Oxid_Esales\Database_Views_Generator\Views_Generator;
interface Views_Generator_Factory_Interface
{
    public function create(): Views_Generator;
}