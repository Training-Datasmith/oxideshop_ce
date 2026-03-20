<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Html;

use Symfony\Component\Html_Sanitizer\Html_Sanitizer_Config;
interface Html_Sanitizer_Config_Factory_Interface
{
    public function create(): Html_Sanitizer_Config;
}