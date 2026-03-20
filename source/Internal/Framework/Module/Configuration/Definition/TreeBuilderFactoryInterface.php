<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Definition;

use Symfony\Component\Config\Definition\Node_Interface;
interface Tree_Builder_Factory_Interface
{
    public function create(): Node_Interface;
}